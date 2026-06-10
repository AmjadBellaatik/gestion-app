<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild document_sequences from the documents table.
 *
 * The documents table (specifically sequence_number + document_year) is the
 * source of truth.  This command scans every (company_id, document_type_id,
 * document_year) tuple with active documents, resets the cache counter to
 * MAX(active sequence_number), and creates any missing sequence rows.
 *
 * --backfill   Populate sequence_number / document_year on legacy rows that
 *              were created before the 2026_06_10_000001 migration.
 * --dry-run    Preview changes without writing.
 */
class RebuildDocumentSequencesCommand extends Command
{
    protected $signature = 'documents:rebuild-sequences
                            {--company=  : Limit to a specific company_id}
                            {--dry-run   : Preview changes without writing}
                            {--backfill  : Populate sequence_number and document_year from legacy document_number strings}';

    protected $description = 'Rebuild document_sequences counters from the documents table (source of truth).';

    public function handle(): int
    {
        if ($this->option('backfill')) {
            $this->backfillIntegerColumns();
            $this->newLine();
        }

        return $this->rebuildSequences();
    }

    private function rebuildSequences(): int
    {
        $dryRun    = $this->option('dry-run');
        $companyId = $this->option('company');

        $groups = Document::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNotNull('document_year')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->select('company_id', 'document_type_id', 'document_year as year')
            ->distinct()
            ->get();

        $sequences = DocumentSequence::withoutGlobalScopes()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->keyBy(fn ($s) => "{$s->company_id}|{$s->document_type_id}|{$s->year}");

        $rows    = [];
        $changed = 0;
        $handled = [];

        foreach ($groups as $g) {
            $type   = DocumentType::find($g->document_type_id);
            $prefix = $type?->prefix ?: 'DOC';
            $key    = "{$g->company_id}|{$g->document_type_id}|{$g->year}";
            $handled[] = $key;

            $maxActive = Document::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id',       $g->company_id)
                ->where('document_type_id', $g->document_type_id)
                ->where('document_year',    $g->year)
                ->max('sequence_number') ?? 0;

            $seq = $sequences->get($key);

            if (! $seq) {
                $changed++;

                if (! $dryRun) {
                    DocumentSequence::create([
                        'company_id'       => $g->company_id,
                        'document_type_id' => $g->document_type_id,
                        'year'             => $g->year,
                        'prefix'           => $prefix,
                        'current_number'   => $maxActive,
                        'padding'          => 4,
                        'yearly_reset'     => true,
                        'brand_id'         => null,
                    ]);
                }

                $rows[] = [$g->company_id, $type?->code ?? $g->document_type_id, $g->year, $prefix, '—', $maxActive,
                    $dryRun ? 'would create' : 'created'];
                continue;
            }

            $old = (int) $seq->current_number;

            if ($old === $maxActive) {
                $rows[] = [$g->company_id, $type?->code ?? $g->document_type_id, $g->year, $prefix, $old, $maxActive, 'ok'];
                continue;
            }

            $direction = $maxActive < $old ? 'reset↓' : 'synced↑';
            $changed++;

            if (! $dryRun) {
                DB::table('document_sequences')
                    ->where('id', $seq->id)
                    ->update(['current_number' => $maxActive, 'prefix' => $prefix]);
            }

            $rows[] = [$g->company_id, $type?->code ?? $g->document_type_id, $g->year, $prefix, $old, $maxActive,
                $dryRun ? "would {$direction}" : $direction];
        }

        foreach ($sequences as $seq) {
            $key = "{$seq->company_id}|{$seq->document_type_id}|{$seq->year}";

            if (in_array($key, $handled, true)) {
                continue;
            }

            $old = (int) $seq->current_number;

            if ($old === 0) {
                continue;
            }

            $changed++;

            if (! $dryRun) {
                DB::table('document_sequences')
                    ->where('id', $seq->id)
                    ->update(['current_number' => 0]);
            }

            $typeName = DocumentType::find($seq->document_type_id)?->code ?? $seq->document_type_id;
            $rows[] = [$seq->company_id, $typeName, $seq->year, $seq->prefix, $old, 0,
                $dryRun ? 'would reset (no docs)' : 'reset (no docs)'];
        }

        if (empty($rows)) {
            $this->info('All sequences are correct. Nothing to do.');
            return self::SUCCESS;
        }

        $this->table(['Company', 'Type', 'Year', 'Prefix', 'Was', 'Now', 'Action'], $rows);
        $this->newLine();

        $this->info($dryRun
            ? "{$changed} change(s) pending (dry run — nothing written)."
            : "{$changed} sequence(s) updated.");

        return self::SUCCESS;
    }

    private function backfillIntegerColumns(): void
    {
        $dryRun    = $this->option('dry-run');
        $companyId = $this->option('company');

        $count = DB::table('documents')
            ->whereNull('deleted_at')
            ->whereNull('sequence_number')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw("document_number REGEXP '^[A-Za-z]+-[0-9]{4}-[0-9]+$'")
            ->count();

        if ($count === 0) {
            $this->info('All active documents already have sequence_number set.');
            return;
        }

        $this->info("Backfilling sequence_number and document_year for {$count} active document(s)...");

        if (! $dryRun) {
            $whereCompany = $companyId ? 'AND company_id = ?' : '';
            $bindings     = $companyId ? [(int) $companyId] : [];

            DB::statement(
                "UPDATE documents
                 SET
                     document_year   = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(document_number, '-', 2), '-', -1) AS UNSIGNED),
                     sequence_number = CAST(SUBSTRING_INDEX(document_number, '-', -1) AS UNSIGNED)
                 WHERE deleted_at IS NULL
                   AND sequence_number IS NULL
                   AND document_number REGEXP '^[A-Za-z]+-[0-9]{4}-[0-9]+\$'
                   {$whereCompany}",
                $bindings
            );

            $this->info("{$count} document(s) backfilled.");
        } else {
            $this->info("{$count} document(s) would be backfilled (dry run).");
        }
    }
}
