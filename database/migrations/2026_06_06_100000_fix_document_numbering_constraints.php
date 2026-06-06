<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DOCUMENT NUMBERING INTEGRITY MIGRATION
 * ----------------------------------------
 * Problem: document_number had a global UNIQUE constraint, meaning two different
 * companies could not both have FAC-2026-0001, and soft-deleted documents
 * permanently blocked their number from being reused.
 *
 * Fixes applied:
 * 1. Replace UNIQUE(document_number) → UNIQUE(company_id, document_number)
 *    — numbers are only required to be unique within a company.
 * 2. Add UNIQUE(company_id, document_type_id, year) on document_sequences
 *    — prevents duplicate sequence rows that would break the locking strategy.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Fix 1: document_number uniqueness scope ───────────────────────────
        // The old global unique index made cross-company number collision
        // impossible to avoid and also blocked reuse of soft-deleted numbers.
        // We replace it with a composite (company_id, document_number) index.
        Schema::table('documents', function (Blueprint $table) {
            // Guard: drop the old global unique index if it still exists.
            // Different migration paths may have named it differently.
            try {
                $table->dropUnique('documents_document_number_unique');
            } catch (\Throwable) {
                // Already absent — nothing to do.
            }

            // New composite unique: a number is unique only within its company.
            // Deleted documents have their document_number mangled to a VOID
            // string (see Document::deleting observer) so this constraint does
            // not block number reuse.
            $table->unique(['company_id', 'document_number'], 'documents_company_document_number_unique');
        });

        // ── Fix 2: document_sequences serialisation key ───────────────────────
        // The sequences table had no unique constraint on (company, type, year).
        // Without it, a race on the very first document of a type in a year
        // could insert two rows, breaking the lockForUpdate serialisation.
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'document_type_id', 'year'],
                'doc_sequences_company_type_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropUnique('doc_sequences_company_type_year_unique');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_company_document_number_unique');

            try {
                $table->unique('document_number', 'documents_document_number_unique');
            } catch (\Throwable) {
                // Restore best-effort; original constraint may not be recoverable.
            }
        });
    }
};
