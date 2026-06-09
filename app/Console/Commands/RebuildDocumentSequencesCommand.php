<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildDocumentSequencesCommand extends Command
{
    protected $signature = 'documents:rebuild-sequences
                            {--company= : Limit rebuild to a specific company_id}
                            {--dry-run  : Show what would change without writing}';

    protected $description = 'Sync document_sequences.current_number to the actual max document number in the documents table. Orphaned counters (from deleted test documents) are reduced to reality.';

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        $companyId = $this->option('company');

        // Collect all (company_id, document_type_id, year) triples that have
        // at least one active document, so we can create missing sequence rows.
        $docGroups = Document::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNotNull('document_number')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->select('company_id', 'document_type_id', DB::raw('YEAR(created_at) as year'))
            ->distinct()
            ->get();

        // Load existing sequence rows
        $sequences = DocumentSequence::withoutGlobalScopes()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->keyBy(fn ($s) => "{$s->company_id}_{$s->document_type_id}_{$s->year}");

        $headers  = ['Company', 'Type', 'Year', 'Prefix', 'Old Counter', 'New Counter', 'Action'];
        $rows     = [];
        $repaired = 0;

        // ── 1. Repair / create sequence rows for every company+type+year that
        //       has actual documents ────────────────────────────────────────
        foreach ($docGroups as $group) {
            $type = DocumentType::find($group->document_type_id);
            $prefix = $type?->prefix ?: 'DOC';
            $key  = "{$group->company_id}_{$group->document_type_id}_{$group->year}";
            $seq  = $sequences->get($key);

            $maxActive = Document::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id',       $group->company_id)
                ->where('document_type_id', $group->document_type_id)
                ->where('document_number', 'like', $prefix . '-' . $group->year . '-%')
                ->get(['document_number'])
                ->map(function ($d): int {
                    $pos = strrpos((string) $d->document_number, '-');
                    return $pos !== false ? (int) substr($d->document_number, $pos + 1) : 0;
                })
                ->max() ?? 0;

            if (! $seq) {
                // Missing sequence row — create it
                $action = $dryRun ? 'would create' : 'created';
                $repaired++;

                if (! $dryRun) {
                    DocumentSequence::create([
                        'company_id'       => $group->company_id,
                        'document_type_id' => $group->document_type_id,
                        'year'             => $group->year,
                        'prefix'           => $prefix,
                        'current_number'   => $maxActive,
                        'padding'          => 4,
                        'yearly_reset'     => true,
                    ]);
                }

                $rows[] = [$group->company_id, $group->document_type_id, $group->year, $prefix, '—', $maxActive, $action];
                continue;
            }

            $oldCounter = (int) $seq->current_number;
            $newCounter = $maxActive; // source of truth is always the documents table

            if ($oldCounter === $newCounter) {
                $rows[] = [$group->company_id, $group->document_type_id, $group->year, $prefix, $oldCounter, $newCounter, 'ok'];
                continue;
            }

            $direction = $newCounter < $oldCounter ? 'reset (orphan)' : 'synced up';
            $action    = $dryRun ? "would {$direction}" : $direction;
            $repaired++;

            if (! $dryRun) {
                DB::table('document_sequences')
                    ->where('id', $seq->id)
                    ->update(['current_number' => $newCounter]);
            }

            $rows[] = [$group->company_id, $group->document_type_id, $group->year, $prefix, $oldCounter, $newCounter, $action];
        }

        // ── 2. Also check existing sequence rows with no remaining active
        //       documents — their counter should be reset to 0 ─────────────
        foreach ($sequences as $seq) {
            $key = "{$seq->company_id}_{$seq->document_type_id}_{$seq->year}";
            $alreadyHandled = $docGroups->first(
                fn ($g) => "{$g->company_id}_{$g->document_type_id}_{$g->year}" === $key
            );

            if ($alreadyHandled) {
                continue;
            }

            $oldCounter = (int) $seq->current_number;

            if ($oldCounter === 0) {
                continue;
            }

            $action = $dryRun ? 'would reset (no docs)' : 'reset (no docs)';
            $repaired++;

            if (! $dryRun) {
                DB::table('document_sequences')
                    ->where('id', $seq->id)
                    ->update(['current_number' => 0]);
            }

            $rows[] = [$seq->company_id, $seq->document_type_id, $seq->year, $seq->prefix, $oldCounter, 0, $action];
        }

        if (empty($rows)) {
            $this->info('All sequences are already correct.');
            return self::SUCCESS;
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($dryRun) {
            $this->info("{$repaired} sequence(s) would be updated (dry run — no changes made).");
        } else {
            $this->info("{$repaired} sequence(s) updated.");
        }

        return self::SUCCESS;
    }
}
