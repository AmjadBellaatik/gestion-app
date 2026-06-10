<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Document Numbering System — Regression & Contract Tests
 * =========================================================
 *
 * These tests verify every business rule governing document number generation:
 *
 *   RULE 1  Only active (non-deleted) documents count toward the next number.
 *   RULE 2  Deleting the LAST number frees it for immediate reuse.
 *   RULE 3  Deleting a MIDDLE number does NOT reuse it; sequence continues from MAX+1.
 *   RULE 4  Deleting ALL documents resets the sequence to 1.
 *   RULE 5  Every document type follows the same rules.
 *   RULE 6  Numbering is isolated per company.
 *   RULE 7  Numbering is isolated per year.
 *   RULE 8  A rolled-back transaction does not permanently reserve a sequence number.
 *   RULE 9  A soft-deleted document keeps its original number; restore reclaims the slot
 *           when free or fails with a DB constraint when the slot has been taken.
 */
class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Company      $company;
    private DocumentType $invoiceType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Test Company A']);

        $this->invoiceType = DocumentType::create([
            'name'     => 'Facture',
            'code'     => DocumentType::INVOICE,
            'prefix'   => 'FAC',
            'category' => 'commercial',
        ]);

        session(['company_id' => $this->company->id]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeDoc(?Company $company = null, ?DocumentType $type = null): Document
    {
        $co = $company ?? $this->company;
        $dt = $type    ?? $this->invoiceType;

        if ($company !== null) {
            session(['company_id' => $co->id]);
        }

        return Document::create([
            'company_id'       => $co->id,
            'document_type_id' => $dt->id,
        ]);
    }

    /** Return the integer sequence stored on the model (no string parsing). */
    private function seq(Document $doc): int
    {
        return (int) $doc->sequence_number;
    }

    private function createMany(int $n, ?Company $company = null, ?DocumentType $type = null): array
    {
        $docs = [];
        for ($i = 0; $i < $n; $i++) {
            $docs[] = $this->makeDoc($company, $type);
        }
        return $docs;
    }

    // ── TEST 1 — Delete the LAST document → next number reuses it ────────────

    public function test_deleting_last_document_reuses_its_number(): void
    {
        $docs = $this->createMany(10);
        $this->assertSame(10, $this->seq($docs[9]));

        $docs[9]->delete();
        $next = $this->makeDoc();

        $this->assertSame(10, $this->seq($next),
            'After deleting the last document, the next one must reuse sequence 10.');
    }

    // ── TEST 2 — Delete a MIDDLE document → next continues from MAX+1 ────────

    public function test_deleting_middle_document_does_not_reuse_its_number(): void
    {
        $docs = $this->createMany(10);
        $this->assertSame(7, $this->seq($docs[6]));

        $docs[6]->delete();
        $next = $this->makeDoc();

        $this->assertSame(11, $this->seq($next),
            'After deleting a middle document, the sequence must continue from MAX(active)+1 = 11.');

        $activeSeqs = Document::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id',       $this->company->id)
            ->where('document_type_id', $this->invoiceType->id)
            ->pluck('sequence_number')
            ->map(fn ($n) => (int) $n)
            ->sort()
            ->values()
            ->toArray();

        $this->assertNotContains(7, $activeSeqs, 'Sequence 7 must remain a gap.');
        $this->assertContains(11, $activeSeqs, 'Sequence 11 must now exist.');
    }

    // ── TEST 3 — Delete ALL documents → sequence resets to 1 ─────────────────

    public function test_deleting_all_documents_resets_sequence_to_one(): void
    {
        $docs = $this->createMany(10);
        foreach ($docs as $doc) {
            $doc->delete();
        }

        $next = $this->makeDoc();
        $this->assertSame(1, $this->seq($next),
            'After deleting all documents the sequence must restart at 1.');
    }

    // ── TEST 4 — Multi-company isolation ──────────────────────────────────────

    public function test_numbering_is_isolated_per_company(): void
    {
        $companyB = Company::create(['name' => 'Test Company B']);

        $docsA = $this->createMany(5, $this->company);
        $docsB = $this->createMany(3, $companyB);

        $this->assertSame(1, $this->seq($docsA[0]));
        $this->assertSame(5, $this->seq($docsA[4]));
        $this->assertSame(1, $this->seq($docsB[0]));
        $this->assertSame(3, $this->seq($docsB[2]));

        $docsA[3]->delete();
        $docsA[4]->delete();

        session(['company_id' => $this->company->id]);
        $nextA = $this->makeDoc($this->company);
        $this->assertSame(4, $this->seq($nextA), 'Company A next must be 4 after deleting 4 and 5.');

        session(['company_id' => $companyB->id]);
        $nextB = $this->makeDoc($companyB);
        $this->assertSame(4, $this->seq($nextB), 'Company B must be on its own independent sequence.');
    }

    // ── TEST 5 — Year isolation ───────────────────────────────────────────────

    public function test_numbering_is_isolated_per_year(): void
    {
        Carbon::setTestNow('2025-06-01');
        $docs2025 = $this->createMany(50);
        $this->assertSame(50, $this->seq($docs2025[49]));

        Carbon::setTestNow('2026-01-01');
        $first2026 = $this->makeDoc();
        $this->assertSame(1, $this->seq($first2026),
            'First document of 2026 must start at sequence 1.');

        $second2026 = $this->makeDoc();
        $this->assertSame(2, $this->seq($second2026));

        $third2026 = $this->makeDoc();
        $this->assertSame(3, $this->seq($third2026));

        $third2026->delete();
        $reused2026 = $this->makeDoc();
        $this->assertSame(3, $this->seq($reused2026),
            'Deleting last 2026 doc must allow reuse of sequence 3.');

        $second2026->delete();
        $afterMiddleDelete = $this->makeDoc();
        $this->assertSame(4, $this->seq($afterMiddleDelete),
            'Deleting middle 2026 doc must continue from MAX(active 2026)+1 = 4.');

        $this->assertDatabaseCount('documents', 55);

        Carbon::setTestNow();
    }

    // ── TEST 6 — Rapid sequential creation yields unique numbers ─────────────

    public function test_rapid_sequential_creation_produces_unique_numbers(): void
    {
        $count = 30;
        $docs  = $this->createMany($count);

        $sequences = array_map(fn ($d) => $this->seq($d), $docs);
        $this->assertSame(range(1, $count), $sequences, 'Sequences must be strictly 1 … 30.');

        $numbers = array_map(fn ($d) => $d->document_number, $docs);
        $this->assertCount($count, array_unique($numbers), 'All document numbers must be unique.');
    }

    // ── TEST 7 — Delete then create: soft-deleted doc keeps original number ───
    //
    // In the new architecture there is NO VOID mangling. The deleted document
    // retains its original document_number. The new active document reuses the
    // same document_number. Both rows coexist in the DB (one active, one deleted).
    // The DB constraint UNIQUE(company, type, year, live_sequence) keeps this safe:
    // the deleted row has live_sequence = NULL (generated column), so it is exempt.

    public function test_delete_then_create_produces_correct_number_without_collision(): void
    {
        $docs = $this->createMany(5);
        $this->assertSame(5, $this->seq($docs[4]));

        $docs[4]->delete();

        $reclaimed = $this->makeDoc();
        $this->assertSame(5, $this->seq($reclaimed), 'Deleted last doc → next must be 5.');

        $year   = now()->year;
        $target = "FAC-{$year}-0005";

        // withoutGlobalScopes() removes both CompanyScope and SoftDeletingScope.
        $allNumbers = Document::withoutGlobalScopes()
            ->where('company_id',       $this->company->id)
            ->where('document_type_id', $this->invoiceType->id)
            ->pluck('document_number')
            ->toArray();

        $exactHits = array_values(array_filter($allNumbers, fn ($n) => $n === $target));

        $this->assertCount(2, $exactHits,
            "Soft-deleted doc keeps its original number, so {$target} must appear exactly twice: once deleted, once active.");

        $activeCount = Document::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id',       $this->company->id)
            ->where('document_type_id', $this->invoiceType->id)
            ->where('document_number',  $target)
            ->count();

        $this->assertSame(1, $activeCount, "Exactly 1 active document must have number {$target}.");
    }

    // ── TEST 8 — All document types obey the same rules ───────────────────────

    public function test_all_document_types_follow_the_same_numbering_rules(): void
    {
        $types = [
            ['name' => 'Devis',            'code' => DocumentType::QUOTATION,         'prefix' => 'DEV'],
            ['name' => 'Bon de livraison', 'code' => DocumentType::DELIVERY_NOTE,     'prefix' => 'BL'],
            ['name' => 'Contrat garantie', 'code' => DocumentType::WARRANTY_CONTRACT, 'prefix' => 'GAR'],
            ['name' => 'Certificat',       'code' => DocumentType::CONFORMITY,        'prefix' => 'CONF'],
            ['name' => 'Bon commande',     'code' => DocumentType::SUPPLIER_ORDER,    'prefix' => 'BC'],
            ['name' => 'Bon de retour',    'code' => DocumentType::SALE_RETURN,       'prefix' => 'RET'],
        ];

        foreach ($types as $typeData) {
            $docType = DocumentType::create([
                'name'     => $typeData['name'],
                'code'     => $typeData['code'],
                'prefix'   => $typeData['prefix'],
                'category' => 'commercial',
            ]);

            $d1 = $this->makeDoc(null, $docType);
            $d2 = $this->makeDoc(null, $docType);
            $d3 = $this->makeDoc(null, $docType);

            $this->assertSame(1, $this->seq($d1), "{$typeData['prefix']}: first must be 1.");
            $this->assertSame(2, $this->seq($d2), "{$typeData['prefix']}: second must be 2.");
            $this->assertSame(3, $this->seq($d3), "{$typeData['prefix']}: third must be 3.");

            $d3->delete();
            $next = $this->makeDoc(null, $docType);
            $this->assertSame(3, $this->seq($next), "{$typeData['prefix']}: delete last → reuse 3.");

            $year = now()->year;
            $this->assertSame(
                "{$typeData['prefix']}-{$year}-0003",
                $next->document_number,
                "{$typeData['prefix']}: number format must be PREFIX-YEAR-NNNN."
            );
        }
    }

    // ── TEST 9 — sequence_number and document_year are stored on new docs ─────

    public function test_new_document_stores_sequence_number_and_document_year(): void
    {
        $year = now()->year;

        $d1 = $this->makeDoc();
        $this->assertSame(1,     (int) $d1->sequence_number,  'd1 sequence_number must be 1.');
        $this->assertSame($year, (int) $d1->document_year,    'd1 document_year must equal current year.');

        $d2 = $this->makeDoc();
        $this->assertSame(2,     (int) $d2->sequence_number,  'd2 sequence_number must be 2.');
        $this->assertSame($year, (int) $d2->document_year);

        // Delete d2 (the last active doc). MAX(active) drops to 1 → next = 2.
        $d2->delete();

        $d3 = $this->makeDoc();
        $this->assertSame(2, (int) $d3->sequence_number,
            'After deleting d2 (last), next must reuse sequence 2.');
        $this->assertSame($year, (int) $d3->document_year);
    }

    // ── TEST 10 — Sequence counter resyncs after multiple deletions ───────────

    public function test_sequence_counter_resyncs_after_multiple_deletions(): void
    {
        $docs = $this->createMany(10);

        $docs[9]->delete();
        $docs[8]->delete();
        $docs[7]->delete();

        $next = $this->makeDoc();
        $this->assertSame(8, $this->seq($next),
            'After deleting docs 8-10, next must be 8 (MAX active = 7).');

        $sequence = DocumentSequence::withoutGlobalScopes()
            ->where('company_id',       $this->company->id)
            ->where('document_type_id', $this->invoiceType->id)
            ->where('year',             now()->year)
            ->first();

        $this->assertNotNull($sequence, 'Sequence row must exist.');
        $this->assertSame(8, $sequence->current_number, 'Sequence current_number must be 8.');
    }

    // ── SCENARIO F — Rolled-back transaction does not reserve a number ────────
    //
    // When a DB::transaction() is rolled back, the document INSERT is undone.
    // The sequence_number is never committed to the documents table.
    // The sequence cache row update is also rolled back.
    // The next call to generate() must therefore produce the same number again.

    public function test_scenario_f_rollback_does_not_reserve_sequence_number(): void
    {
        DB::beginTransaction();

        $doc = Document::create([
            'company_id'       => $this->company->id,
            'document_type_id' => $this->invoiceType->id,
        ]);

        $this->assertSame(1, (int) $doc->sequence_number, 'First document in rolled-back txn must get sequence 1.');

        DB::rollBack();

        // The INSERT was rolled back — no documents exist.
        $this->assertDatabaseCount('documents', 0);

        // The next document must get sequence 1 again (rollback freed it).
        $next = $this->makeDoc();
        $this->assertSame(1, $this->seq($next),
            'Rolled-back INSERT must not permanently reserve the sequence number.');
    }

    // ── SCENARIO G — Soft delete and restore is deterministic ────────────────
    //
    // Case A: Delete doc, no replacement → restore succeeds, doc reclaims its slot.
    // Case B: Delete doc, create replacement (takes the slot) → restore fails
    //         with a DB unique constraint violation (live_sequence conflict).

    public function test_scenario_g_restore_succeeds_when_slot_is_free(): void
    {
        [$d1, $d2, $d3] = $this->createMany(3);
        $this->assertSame(3, $this->seq($d3));

        $d3->delete();

        $d3->restore();
        $d3->refresh();

        $this->assertNull($d3->deleted_at, 'Document must be active after restore.');
        $this->assertSame(3, $this->seq($d3), 'Restored document must keep sequence 3.');
    }

    public function test_scenario_g_restore_fails_when_slot_is_taken(): void
    {
        [$d1, $d2, $d3] = $this->createMany(3);

        $d3->delete();

        // Create a replacement that takes slot 3.
        $replacement = $this->makeDoc();
        $this->assertSame(3, $this->seq($replacement), 'Replacement must reuse sequence 3.');

        // Attempting to restore d3 would set live_sequence = 3, conflicting with the replacement.
        $threw = false;
        try {
            $d3->restore();
        } catch (\Illuminate\Database\QueryException $e) {
            $threw = true;
        }

        $this->assertTrue($threw,
            'Restoring a document whose sequence slot has been taken must throw a constraint exception.');
    }
}
