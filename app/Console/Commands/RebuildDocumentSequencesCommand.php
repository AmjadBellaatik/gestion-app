<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentSequence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildDocumentSequencesCommand extends Command
{
    protected $signature = 'documents:rebuild-sequences
                            {--company= : Limit rebuild to a specific company_id}
                            {--dry-run  : Show what would change without writing}';

    protected $description = 'Set each document_sequences.current_number to max(existing document number) per company/year/type';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $query = DocumentSequence::withoutGlobalScopes();

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        $sequences = $query->get();

        if ($sequences->isEmpty()) {
            $this->info('No document sequences found.');
            return self::SUCCESS;
        }

        $headers  = ['Company', 'Type ID', 'Year', 'Prefix', 'Old Counter', 'New Counter', 'Action'];
        $rows     = [];
        $repaired = 0;

        foreach ($sequences as $seq) {
            $maxActive = Document::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id',       $seq->company_id)
                ->where('document_type_id', $seq->document_type_id)
                ->where('document_number', 'like', $seq->prefix . '-' . $seq->year . '-%')
                ->get(['document_number'])
                ->map(function ($d) {
                    $pos = strrpos((string) $d->document_number, '-');
                    return $pos !== false ? (int) substr($d->document_number, $pos + 1) : 0;
                })
                ->max() ?? 0;

            $newCounter = max($maxActive, (int) $seq->current_number);
            $action     = 'no change';

            if ($newCounter !== (int) $seq->current_number) {
                $action = $dryRun ? 'would update' : 'updated';
                $repaired++;

                if (! $dryRun) {
                    DB::table('document_sequences')
                        ->where('id', $seq->id)
                        ->update(['current_number' => $newCounter]);
                }
            }

            $rows[] = [
                $seq->company_id,
                $seq->document_type_id,
                $seq->year,
                $seq->prefix,
                $seq->current_number,
                $newCounter,
                $action,
            ];
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
