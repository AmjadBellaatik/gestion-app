<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate the next document number for $document, write sequence_number
     * and document_year onto the model, and return the formatted string.
     *
     * Algorithm: next = MAX(sequence_number of active docs for this company + type + year) + 1
     *
     * Active     = deleted_at IS NULL.
     * Source of truth = documents.sequence_number (integer, no string parsing).
     * Sequence table  = named mutex + display cache only.
     *
     * Soft-deleted documents keep their original document_number and sequence_number.
     * The DB constraint UNIQUE(company_id, document_type_id, document_year, live_sequence)
     * enforces per-company-type-year uniqueness for active documents only
     * (live_sequence is a generated column: NULL when deleted_at IS NOT NULL).
     */
    public static function generate(Document $document): string
    {
        $companyId = $document->company_id;
        $typeId    = $document->document_type_id;
        $year      = now()->year;
        $type      = $document->documentType ?? DocumentType::find($typeId);
        $prefix    = $type?->prefix ?: 'DOC';

        return DB::transaction(function () use ($document, $companyId, $typeId, $year, $prefix) {
            // Step 1: acquire exclusive lock on the sequence row (named mutex).
            $sequence = DocumentSequence::withoutGlobalScopes()
                ->where('company_id',       $companyId)
                ->where('document_type_id', $typeId)
                ->where('year',             $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                try {
                    $sequence = DocumentSequence::create([
                        'company_id'       => $companyId,
                        'document_type_id' => $typeId,
                        'year'             => $year,
                        'prefix'           => $prefix,
                        'current_number'   => 0,
                        'padding'          => 4,
                        'yearly_reset'     => true,
                        'brand_id'         => null,
                    ]);
                } catch (QueryException) {
                    // First-row race: re-read under lock.
                    $sequence = DocumentSequence::withoutGlobalScopes()
                        ->where('company_id',       $companyId)
                        ->where('document_type_id', $typeId)
                        ->where('year',             $year)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            // Step 2: MAX(sequence_number) from active documents — sole source of truth.
            // lockForUpdate() forces a current read in REPEATABLE READ isolation.
            $maxActive = Document::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id',       $companyId)
                ->where('document_type_id', $typeId)
                ->where('document_year',    $year)
                ->lockForUpdate()
                ->max('sequence_number') ?? 0;

            $next = (int) $maxActive + 1;

            // Step 3: persist integer fields onto the model so the creating observer
            // can include them in the INSERT.
            $document->sequence_number = $next;
            $document->document_year   = $year;

            // Step 4: update the sequence cache.
            DB::table('document_sequences')
                ->where('id', $sequence->id)
                ->update(['current_number' => $next, 'prefix' => $prefix]);

            return $prefix . '-' . $year . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }
}
