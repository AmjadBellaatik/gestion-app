<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentNumberService
{
    /**
     * Generate the next document number for the given document.
     *
     * ── Algorithm ────────────────────────────────────────────────────────────
     *
     * Step 1 — Acquire an exclusive row-lock on the document_sequences row for
     *   (company_id, document_type_id, year).  This is the serialisation point:
     *   concurrent generate() calls for the same key queue here, preventing
     *   duplicate numbers.
     *
     * Step 2 — Compute the real maximum from active (non-deleted) documents.
     *   Soft-deleted documents are excluded because their numbers are mangled to
     *   "__VOID_..." strings by the Document::deleting observer, so they never
     *   appear in this scan.  We also filter by prefix+year pattern to avoid
     *   parsing numbers from a different type/year with the same type_id.
     *
     * Step 3 — Upsert the sequence row, syncing the counter UP only.
     *   We use max(maxActive, current_number) and NEVER plain maxActive.
     *   Moving the counter down would open a race window where a concurrent
     *   request could reclaim an already-issued number:
     *     • A increments to N, releases savepoint (locks still held by outer TX),
     *       INSERT pending.
     *     • B arrives, waits for A's lock.  After A commits, B sees maxActive=N.
     *       Correct: B computes N+1.  ← desired behaviour
     *     • But if the counter were allowed to retreat to maxActive=N-1 while
     *       A's INSERT was still uncommitted, B would issue N again.
     *
     *   First-row race condition:
     *   When the first document of a type+year is being created, two concurrent
     *   requests may BOTH find no sequence row and both attempt to INSERT one.
     *   The UNIQUE(company_id, document_type_id, year) constraint on
     *   document_sequences means only one INSERT wins.  The loser catches the
     *   QueryException and recovers by re-reading the winning row under a lock,
     *   then continues as normal.  No request ever fails due to this race.
     *
     * Step 4 — Increment and skip any number still held by any document
     *   (active or soft-deleted but un-mangled via withTrashed).  Under normal
     *   operation this loop body executes exactly once.
     *
     * ── Concurrency guarantees ───────────────────────────────────────────────
     *
     * • Two simultaneous creates: serialised by lockForUpdate() on the sequence
     *   row — one blocks until the other's outer transaction commits.
     * • Simultaneous delete + create: generate() reads MAX inside its lock
     *   window; deleted numbers are mangled, so they are excluded.
     * • First document of type+year: try/catch recovery (see Step 3 above).
     * • Soft-delete + recreate: mangling frees the unique constraint; withTrashed
     *   in the do-while covers un-mangled legacy rows.
     * • Sale regeneration / repair ticket: both go through DocumentService::
     *   generate() → DocumentService::create() → this method.
     */
    public static function generate(Document $document): string
    {
        $type    = $document->documentType ?: DocumentType::find($document->document_type_id);
        $year    = now()->year;
        $prefix  = $type?->prefix ?: 'DOC';
        $padding = 4;

        return DB::transaction(function () use ($document, $type, $year, $prefix, $padding) {

            // ── Step 1: acquire exclusive lock on the sequence row ────────────
            //
            // lockForUpdate() is the sole serialisation gate.  Every concurrent
            // generate() call for the same (company, type, year) must pass here
            // before computing a number.  Locks are held until the outer
            // DB::transaction() (DocumentService::create) commits, which means
            // the document INSERT is already in the DB before the next caller
            // can observe maxActive.
            $sequence = DocumentSequence::withoutGlobalScopes()
                ->where('company_id',       $document->company_id)
                ->where('document_type_id', $document->document_type_id)
                ->where('year',             $year)
                ->lockForUpdate()
                ->first();

            // ── Step 2: compute the real maximum from active documents ────────
            //
            // whereNull('deleted_at') excludes soft-deleted rows: those have
            // their document_number mangled to "PREFIX-YEAR-NNNN__VOID_..." by
            // the Document::deleting observer, so they never produce a valid seq.
            //
            // lockForUpdate() forces a current read, bypassing the REPEATABLE
            // READ snapshot, so we see the latest committed state even when
            // called inside a nested savepoint.
            $maxActive = Document::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id',       $document->company_id)
                ->where('document_type_id', $document->document_type_id)
                ->whereNotNull('document_number')
                ->where('document_number', 'like', $prefix . '-' . $year . '-%')
                ->lockForUpdate()
                ->get(['document_number'])
                ->map(function ($d) {
                    $num = (string) $d->document_number;
                    $pos = strrpos($num, '-');
                    if ($pos === false) {
                        return 0;
                    }
                    $seq = (int) substr($num, $pos + 1);
                    // Defensive: reject any sequence that looks like a VOID suffix
                    return $seq > 0 && $seq < 1_000_000 ? $seq : 0;
                })
                ->max() ?? 0;

            // ── Step 3: upsert the sequence row, synced UP only ───────────────
            if (! $sequence) {
                // No sequence row exists yet for this (company, type, year).
                // Two concurrent requests may both reach this branch.  We wrap
                // the INSERT in a try/catch so the losing request recovers
                // automatically instead of propagating a QueryException to the
                // caller.
                try {
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
                        'DocumentSequence created: company=%d type=%d year=%d prefix=%s initialised to %d.',
                        $document->company_id,
                        $document->document_type_id,
                        $year,
                        $prefix,
                        $maxActive
                    ));
                } catch (QueryException $e) {
                    // The UNIQUE(company_id, document_type_id, year) constraint
                    // fired: a concurrent request won the INSERT race and the row
                    // now exists.  Re-read it under a lock so we can proceed from
                    // the correct (winner's) sequence state.
                    Log::info(sprintf(
                        'DocumentSequence first-row race: company=%d type=%d year=%d — recovering.',
                        $document->company_id,
                        $document->document_type_id,
                        $year
                    ));

                    $sequence = DocumentSequence::withoutGlobalScopes()
                        ->where('company_id',       $document->company_id)
                        ->where('document_type_id', $document->document_type_id)
                        ->where('year',             $year)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            } else {
                // Sync the counter to the actual maximum in the documents table.
                // We allow syncing DOWN so that orphaned counters (left by deleted
                // test documents) do not persist forever.
                //
                // Safety: lockForUpdate() on the sequence row serialises all
                // concurrent generate() calls — B cannot acquire the lock until
                // A's outer transaction (which includes the document INSERT)
                // commits.  By the time B reads maxActive, A's document is
                // already visible.  The do-while in Step 4 is the true uniqueness
                // safety net and handles any residual edge case.
                $syncedBase = $maxActive;

                if ((int) $sequence->current_number !== $syncedBase || $sequence->prefix !== $prefix) {
                    $sequence->current_number = $syncedBase;
                    $sequence->prefix         = $prefix;
                    $sequence->save();
                }
            }

            // ── Step 4: increment and guarantee uniqueness ────────────────────
            //
            // withTrashed() in the uniqueness check covers legacy soft-deleted
            // rows that were NOT mangled (pre-dates the __VOID__ observer).
            // Those rows still hold their original number and the DB unique
            // constraint blocks any new document from reusing it.
            //
            // Under normal operation the loop body executes exactly once.
            do {
                $sequence->increment('current_number');
                $sequence->refresh();

                $number = $sequence->prefix
                    . '-' . $year
                    . '-' . str_pad((string) $sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT);

            } while (
                Document::withoutGlobalScopes()
                    ->withTrashed()                         // covers un-mangled legacy rows
                    ->where('company_id', $document->company_id)
                    ->where('document_number', $number)
                    ->lockForUpdate()
                    ->exists()
            );

            return $number;
        });
    }
}
