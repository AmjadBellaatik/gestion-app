<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use App\Services\Documents\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
 *
 * Concurrency note (TEST 6 / TEST 7):
 *   PHP unit tests are single-threaded; actual concurrent isolation is enforced
 *   by lockForUpdate() on the document_sequences row in MySQL production.
 *   Tests here verify the serialisation logic produces correct sequential results
 *   when called in rapid succession from the same process.
 */
class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Shared fixtures
    // ─────────────────────────────────────────────────────────────────────────

    private Company      $company;
    private DocumentType $invoiceType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Test Company A']);

        // DocumentType is now global (company_id = null) as of the
        // make_document_types_global migration.
        $this->invoiceType = DocumentType::create([
            'name'     => 'Facture',
            'code'     => DocumentType::INVOICE,
            'prefix'   => 'FAC',
            'category' => 'commercial',
        ]);

        // Put the company into the session so CompanyScope does not reject
        // queries that do not use withoutGlobalScopes().
        session(['company_id' => $this->company->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Create one document for $company using $type (defaults to invoiceType). */
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

    /** Extract the trailing numeric part of a document number (e.g. "FAC-2026-0007" → 7). */
    private function seq(Document $doc): int
    {
        $num = (string) $doc->document_number;
        return (int) substr($num, strrpos($num, '-') + 1);
    }

    /** Create $n documents and return them as an array. */
    private function createMany(int $n, ?Company $company = null, ?DocumentType $type = null): array
    {
        $docs = [];
        for ($i = 0; $i < $n; $i++) {
            $docs[] = $this->makeDoc($company, $type);
        }
        return $docs;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 1 — Delete the LAST document → next number reuses it
    //
    // RULE 2: FAC-…-0001 … FAC-…-0010 exist.
    //         Delete FAC-…-0010.
    //         MAX(active) drops to 9 → next generated = 0010.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_deleting_last_document_reuses_its_number(): void
    {
        $docs = $this->createMany(10);

        $this->assertSame(10, $this->seq($docs[9]), 'Sanity: 10th document should have sequence 10.');

        $docs[9]->delete();

        $next = $this->makeDoc();

        $this->assertSame(
            10,
            $this->seq($next),
            'After deleting the last document, the next one must reuse sequence 10.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 2 — Delete a MIDDLE document → next number continues from MAX+1
    //
    // RULE 3: FAC-…-0001 … FAC-…-0010 exist.
    //         Delete FAC-…-0007.
    //         MAX(active) is still 0010 → next generated = 0011.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_deleting_middle_document_does_not_reuse_its_number(): void
    {
        $docs = $this->createMany(10);

        $this->assertSame(7, $this->seq($docs[6]), 'Sanity: 7th document should have sequence 7.');

        $docs[6]->delete(); // Delete FAC-…-0007

        $next = $this->makeDoc();

        $this->assertSame(
            11,
            $this->seq($next),
            'After deleting a middle document, the sequence must continue from MAX(active)+1 = 11.'
        );

        // Confirm that 0007 remains a gap in the active document list.
        $activeNumbers = Document::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id', $this->company->id)
            ->where('document_type_id', $this->invoiceType->id)
            ->pluck('document_number')
            ->map(fn ($n) => (int) substr($n, strrpos($n, '-') + 1))
            ->sort()
            ->values()
            ->toArray();

        $this->assertNotContains(7, $activeNumbers, 'Sequence 7 must remain a gap.');
        $this->assertContains(11, $activeNumbers, 'Sequence 11 must now exist.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 3 — Delete ALL documents → next number resets to 1
    //
    // RULE 4: FAC-…-0001 … FAC-…-0010 all deleted.
    //         MAX(active) = 0 → next generated = 0001.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_deleting_all_documents_resets_sequence_to_one(): void
    {
        $docs = $this->createMany(10);

        foreach ($docs as $doc) {
            $doc->delete();
        }

        $next = $this->makeDoc();

        $this->assertSame(
            1,
            $this->seq($next),
            'After deleting all documents the sequence must restart at 1.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 4 — Multi-company isolation
    //
    // RULE 6: Company A and Company B each generate documents independently.
    //         Deletions in Company A must never affect Company B's sequence.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_numbering_is_isolated_per_company(): void
    {
        $companyB = Company::create(['name' => 'Test Company B']);

        // Company A: create 5 documents
        $docsA = $this->createMany(5, $this->company);

        // Company B: create 3 documents
        $docsB = $this->createMany(3, $companyB);

        // Verify Company A sequences are 1–5
        $this->assertSame(1, $this->seq($docsA[0]));
        $this->assertSame(5, $this->seq($docsA[4]));

        // Verify Company B sequences are 1–3 (independent counter)
        $this->assertSame(1, $this->seq($docsB[0]));
        $this->assertSame(3, $this->seq($docsB[2]));

        // Delete last two documents of Company A (seq 4 and 5)
        $docsA[3]->delete();
        $docsA[4]->delete();

        // Company A: next must be 4 (MAX(active A) = 3)
        session(['company_id' => $this->company->id]);
        $nextA = $this->makeDoc($this->company);
        $this->assertSame(4, $this->seq($nextA), 'Company A next must be 4 after deleting 4 and 5.');

        // Company B: next must be 4 — completely unaffected by Company A changes
        session(['company_id' => $companyB->id]);
        $nextB = $this->makeDoc($companyB);
        $this->assertSame(4, $this->seq($nextB), 'Company B must be on its own independent sequence.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 5 — Year isolation
    //
    // RULE 7: FAC-2025-XXXX must not affect FAC-2026-XXXX.
    //         Documents from a prior year do not count toward the current year.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_numbering_is_isolated_per_year(): void
    {
        // Simulate creating documents in 2025
        Carbon::setTestNow('2025-06-01');
        $docs2025 = $this->createMany(50);
        $this->assertSame(50, $this->seq($docs2025[49]), 'Sanity: should have 50 docs in 2025.');

        // Move to 2026 — sequence must restart from 1 regardless of how many 2025 docs exist
        Carbon::setTestNow('2026-01-01');
        $first2026 = $this->makeDoc();
        $this->assertSame(
            1,
            $this->seq($first2026),
            'First document of 2026 must start at sequence 1 (year isolated from 2025).'
        );

        $second2026 = $this->makeDoc();
        $this->assertSame(2, $this->seq($second2026), '2026 counter continues independently from 2025.');

        $third2026 = $this->makeDoc();
        $this->assertSame(3, $this->seq($third2026));

        // Delete the LAST 2026 document (seq 3) — next must reuse 3 (RULE 2)
        $third2026->delete();
        $reused2026 = $this->makeDoc();
        $this->assertSame(
            3,
            $this->seq($reused2026),
            'Deleting last 2026 doc must allow reuse of sequence 3.'
        );

        // Delete a MIDDLE doc (seq 2) while seq 1 and reused-3 still exist — next must be 4 (RULE 3)
        $second2026->delete();
        $afterMiddleDelete = $this->makeDoc();
        $this->assertSame(
            4,
            $this->seq($afterMiddleDelete),
            'Deleting middle 2026 doc must continue from MAX(active 2026)+1 = 4.'
        );

        // The 2025 documents are completely untouched (50 active + 5 2026 rows = 55 total)
        // first2026(active) + second2026(deleted) + third2026(deleted) + reused2026(active) + afterMiddleDelete(active)
        $this->assertDatabaseCount('documents', 55);

        Carbon::setTestNow(); // Reset
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 6 — Rapid sequential creation yields unique numbers
    //
    // Simulates the concurrency guarantee: even when generate() is called
    // back-to-back (no sleep), every number must be unique.
    //
    // True multi-process concurrency is enforced by lockForUpdate() in MySQL;
    // this test verifies the algorithm produces no duplicates under load.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_rapid_sequential_creation_produces_unique_numbers(): void
    {
        $count = 30;
        $docs  = $this->createMany($count);

        $numbers = array_map(fn ($d) => $d->document_number, $docs);
        $unique  = array_unique($numbers);

        $this->assertCount(
            $count,
            $unique,
            "All {$count} documents must have unique document numbers."
        );

        // Numbers must be strictly sequential (1 … 30)
        $sequences = array_map(fn ($d) => $this->seq($d), $docs);
        $this->assertSame(range(1, $count), $sequences, 'Sequences must be strictly 1 … 30.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 7 — Delete then create: correct number, no collision
    //
    // Simulates a user deleting a document and immediately creating a new one.
    // Verifies the freed number is correctly reclaimed without any duplicate.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_delete_then_create_produces_correct_number_without_collision(): void
    {
        // Phase 1: create 5 documents
        $docs = $this->createMany(5);
        $this->assertSame(5, $this->seq($docs[4]));

        // Phase 2: delete doc 5 (last)
        $docs[4]->delete();

        // Phase 3: create a new one — should reclaim 5
        $reclaimed = $this->makeDoc();
        $this->assertSame(5, $this->seq($reclaimed), 'Deleted last doc → next must be 5.');

        // Phase 4: verify no duplicate exists in DB (including deleted records)
        $allNumbers = Document::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('document_type_id', $this->invoiceType->id)
            ->pluck('document_number')
            ->toArray();

        // The deleted record has its number mangled to a VOID string,
        // so the raw "FAC-YYYY-0005" should appear exactly once (the new doc).
        $year      = now()->year;
        $target    = "FAC-{$year}-0005";
        $exactHits = array_filter($allNumbers, fn ($n) => $n === $target);

        $this->assertCount(
            1,
            $exactHits,
            "Exact number {$target} must appear exactly once in the DB (no duplicate)."
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 8 — All document types obey the same rules (RULE 5)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_all_document_types_follow_the_same_numbering_rules(): void
    {
        $types = [
            ['name' => 'Devis',              'code' => DocumentType::QUOTATION,       'prefix' => 'DEV'],
            ['name' => 'Bon de livraison',   'code' => DocumentType::DELIVERY_NOTE,   'prefix' => 'BL'],
            ['name' => 'Contrat garantie',   'code' => DocumentType::WARRANTY_CONTRACT, 'prefix' => 'GAR'],
            ['name' => 'Certificat',         'code' => DocumentType::CONFORMITY,      'prefix' => 'CONF'],
            ['name' => 'Bon commande',       'code' => DocumentType::SUPPLIER_ORDER,  'prefix' => 'BC'],
            ['name' => 'Facture réparation', 'code' => DocumentType::REPAIR_INVOICE,  'prefix' => 'FREP'],
        ];

        foreach ($types as $typeData) {
            $docType = DocumentType::create([
                'name'     => $typeData['name'],
                'code'     => $typeData['code'],
                'prefix'   => $typeData['prefix'],
                'category' => 'commercial',
            ]);

            // Create 3 documents of this type
            $d1 = $this->makeDoc(null, $docType);
            $d2 = $this->makeDoc(null, $docType);
            $d3 = $this->makeDoc(null, $docType);

            $this->assertSame(1, $this->seq($d1), "{$typeData['prefix']}: first must be 1.");
            $this->assertSame(2, $this->seq($d2), "{$typeData['prefix']}: second must be 2.");
            $this->assertSame(3, $this->seq($d3), "{$typeData['prefix']}: third must be 3.");

            // Delete last → next must reuse 3
            $d3->delete();
            $next = $this->makeDoc(null, $docType);
            $this->assertSame(3, $this->seq($next), "{$typeData['prefix']}: delete last → reuse 3.");

            // Prefix format must be correct
            $year = now()->year;
            $this->assertSame(
                "{$typeData['prefix']}-{$year}-0003",
                $next->document_number,
                "{$typeData['prefix']}: number format must be PREFIX-YEAR-NNNN."
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 9 — VOID mangling: deleted document number is freed in DB
    //
    // The deleting observer must rename the document_number to a VOID string
    // so the (company_id, document_number) unique constraint does not block
    // the reuse of the freed number.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_soft_delete_mangles_document_number_to_free_it(): void
    {
        $doc = $this->makeDoc();
        $originalNumber = $doc->document_number;

        $doc->delete();

        $doc->refresh();

        // The stored number must be mangled
        $this->assertStringContainsString(
            '__VOID_',
            $doc->document_number,
            'Soft-deleted document must have its number mangled with __VOID_ suffix.'
        );

        // The original number must be preserved in metadata
        $this->assertSame(
            $originalNumber,
            $doc->metadata['original_document_number'] ?? null,
            'Original document number must be preserved in metadata.original_document_number.'
        );

        // The mangled number must start with the original (audit traceability)
        $this->assertStringStartsWith(
            $originalNumber,
            $doc->document_number,
            'Mangled number must start with the original number for traceability.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 10 — Sequence counter is resynced after multiple deletions
    //
    // If docs 8, 9, 10 are deleted (in any order), the sequence counter must
    // resync to 7 (MAX of active) and the next number must be 8.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_sequence_counter_resyncs_after_multiple_deletions(): void
    {
        $docs = $this->createMany(10);

        // Delete the last three in reverse order
        $docs[9]->delete(); // was 10
        $docs[8]->delete(); // was  9
        $docs[7]->delete(); // was  8

        // Next number must be 8 (MAX active = 7, next = 8)
        $next = $this->makeDoc();
        $this->assertSame(
            8,
            $this->seq($next),
            'After deleting docs 8-10, next must be 8 (MAX active = 7).'
        );

        // Sequence row must reflect the new current_number = 8
        $sequence = DocumentSequence::withoutGlobalScopes()
            ->where('company_id',       $this->company->id)
            ->where('document_type_id', $this->invoiceType->id)
            ->where('year',             now()->year)
            ->first();

        $this->assertNotNull($sequence, 'Sequence row must exist.');
        $this->assertSame(8, $sequence->current_number, 'Sequence current_number must be 8.');
    }
}
