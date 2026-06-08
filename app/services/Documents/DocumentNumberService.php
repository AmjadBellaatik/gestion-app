<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentNumberService
{
    /**
     * Generate the next document number for the given document.
     *
     * Algorithm (all within a single serialised transaction):
     *
     *   1. Acquire an exclusive row-lock on the document_sequences row for
     *      (company_id, document_type_id, year).  This is the serialisation
     *      point — concurrent generate() calls for the same key are queued
     *      here, preventing duplicate numbers.
     *
     *   2. Derive the true highest active sequence number by scanning
     *      document_number values of non-deleted documents.  The stored
     *      current_number is only a cache; we resync it here to handle
     *      deletes that happened between two generate() calls.
     *
     *      Business rules satisfied:
     *        • Delete the LAST number  → next = that number   (MAX drops by 1)
     *        • Delete a MIDDLE number  → next = MAX + 1       (MAX unchanged)
     *        • Delete ALL documents   → next = 1              (MAX = 0)
     *
     *   3. Increment from the resynced base and loop past any number that an
     *      active document already holds (guards against data migrations or
     *      other edge cases that could create out-of-band document numbers).
     *
     * Concurrency:
     *   • Two simultaneous create requests: serialised by lockForUpdate() on
     *     the sequence row — one waits until the first commits.
     *   • Simultaneous delete + create: the generate() transaction reads the
     *     MAX inside its lock window; REPEATABLE READ ensures it sees a
     *     consistent snapshot.  No duplicate numbers can result.
     *   • First document of a type in a year (no sequence row yet): the
     *     UNIQUE(company_id, document_type_id, year) constraint on
     *     document_sequences means only one INSERT can succeed; if two
     *     concurrent requests race here, one gets a QueryException and the
     *     whole document creation fails gracefully (the user retries).
     *     This window is one millisecond wide and happens at most once per
     *     type per year.
     */
    public static function generate(Document $document): string
    {
        $type    = $document->documentType ?: DocumentType::find($document->document_type_id);
        $year    = now()->year;
        $prefix  = $type?->prefix ?: 'DOC';
        $padding = 4;

        return DB::transaction(function () use ($document, $type, $year, $prefix, $padding) {

            // ── Step 1: acquire exclusive lock on the sequence row ────────────
            $sequence = DocumentSequence::withoutGlobalScopes()
                ->where('company_id',       $document->company_id)
                ->where('document_type_id', $document->document_type_id)
                ->where('year',             $year)
                ->lockForUpdate()
                ->first();

            // ── Step 2: compute the real maximum from active documents ────────
            //
            // We ONLY count documents that are NOT soft-deleted (whereNull).
            // Soft-deleted documents have their document_number mangled to a
            // VOID string (e.g. "FAC-2026-0005__VOID_20260606120000_42") by the
            // Document::deleting observer, so they never pollute this MAX.
            //
            // We also filter by prefix+year pattern so we never accidentally
            // parse a VOID-mangled number or a number from a different type/year
            // that shares the same document_type_id (defensive).
            $maxActive = Document::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id',       $document->company_id)
                ->where('document_type_id', $document->document_type_id)
                ->whereNotNull('document_number')
                ->where('document_number', 'like', $prefix . '-' . $year . '-%')
                ->lockForUpdate()   // force current read — bypass REPEATABLE READ snapshot
                ->get(['document_number'])
                ->map(function ($d) {
                    $num = (string) $d->document_number;
                    $pos = strrpos($num, '-');
                    if ($pos === false) {
                        return 0;
                    }
                    $seq = (int) substr($num, $pos + 1);
                    // Guard: skip VOID-mangled numbers that somehow slipped through
                    return $seq > 0 && $seq < 1_000_000 ? $seq : 0;
                })
                ->max() ?? 0;

            // ── Step 3: upsert the sequence row, resynced to reality ──────────
            if (! $sequence) {
                $sequence = DocumentSequence::create([
                    'company_id'       => $document->company_id,
                    'brand_id'         => null,
                    'document_type_id' => $type?->id ?? $document->document_type_id,
                    'year'             => $year,
                    'prefix'           => $prefix,
                    'current_number'   => $maxActive,
                    'padding'          => $padding,
                    'yearly_reset'     => true,
                ]);

                Log::info(sprintf(
                    'DocumentSequence created: company=%d type=%d year=%d initialised to %d.',
                    $document->company_id,
                    $document->document_type_id,
                    $year,
                    $maxActive
                ));
            } else {
                // Resync counter to what the documents table actually holds.
                // If documents were deleted since the last generate() call, the
                // stored current_number is higher than the real max — bringing
                // it down lets us reuse the freed tail number on the next call.
                if ($sequence->current_number !== $maxActive || $sequence->prefix !== $prefix) {
                    $sequence->current_number = $maxActive;
                    $sequence->prefix         = $prefix;
                    $sequence->save();
                }
            }

            // ── Step 4: increment and guarantee uniqueness among active docs ──
            //
            // The loop is a safety net for data-migration edge cases where an
            // active document holds a number higher than the MAX we computed
            // (e.g. a number was inserted directly into the DB).
            // Under normal operation the loop body executes exactly once.
            do {
                $sequence->increment('current_number');
                $sequence->refresh();

                $number = $sequence->prefix
                    . '-' . $year
                    . '-' . str_pad((string) $sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT);

            } while (
                Document::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where('company_id', $document->company_id)
                    ->where('document_number', $number)
                    ->lockForUpdate()   // force current read — bypass REPEATABLE READ snapshot
                    ->exists()
            );

            return $number;
        });
    }
}
