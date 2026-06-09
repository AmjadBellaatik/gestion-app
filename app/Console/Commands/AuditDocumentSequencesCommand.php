<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentSequence;
use Illuminate\Console\Command;

class AuditDocumentSequencesCommand extends Command
{
    protected $signature = 'documents:audit-sequences
                            {--company= : Limit audit to a specific company_id}';

    protected $description = 'Compare document_sequences counters against actual documents and report discrepancies';

    public function handle(): int
    {
        $query = DocumentSequence::withoutGlobalScopes();

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        $sequences = $query->get();

        if ($sequences->isEmpty()) {
            $this->info('No document sequences found.');
            return self::SUCCESS;
        }

        $headers = ['Company', 'Type ID', 'Year', 'Prefix', 'Seq Counter', 'Max Doc #', 'Status'];
        $rows    = [];
        $issues  = 0;

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

            $status = 'OK';
            if ($maxActive > (int) $seq->current_number) {
                $status = 'COUNTER BEHIND (orphaned docs?)';
                $issues++;
            } elseif ($maxActive === 0 && (int) $seq->current_number > 0) {
                $status = 'ORPHANED SEQUENCE (no matching docs)';
                $issues++;
            }

            $rows[] = [
                $seq->company_id,
                $seq->document_type_id,
                $seq->year,
                $seq->prefix,
                $seq->current_number,
                $maxActive,
                $status,
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($issues > 0) {
            $this->warn("{$issues} sequence(s) have discrepancies. Run documents:rebuild-sequences to repair.");
            return self::FAILURE;
        }

        $this->info('All document sequences are consistent.');
        return self::SUCCESS;
    }
}
