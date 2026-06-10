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

        $headers = ['Company', 'Type ID', 'Year', 'Prefix', 'Seq Counter', 'Max Seq#', 'Status'];
        $rows    = [];
        $issues  = 0;

        foreach ($sequences as $seq) {
            $maxActive = Document::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id',       $seq->company_id)
                ->where('document_type_id', $seq->document_type_id)
                ->where('document_year',    $seq->year)
                ->max('sequence_number') ?? 0;

            $status = 'OK';
            if ($maxActive > (int) $seq->current_number) {
                $status = 'COUNTER BEHIND';
                $issues++;
            } elseif ($maxActive === 0 && (int) $seq->current_number > 0) {
                $status = 'ORPHANED SEQUENCE';
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
