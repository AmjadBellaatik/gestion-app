<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the two new integer columns.
        Schema::table('documents', function (Blueprint $table) {
            $table->smallInteger('document_year')->unsigned()->nullable()->after('document_date');
            $table->unsignedInteger('sequence_number')->nullable()->after('document_year');
        });

        // 2. Add virtual generated column.
        //    live_sequence = sequence_number for active docs, NULL for soft-deleted docs.
        //    MySQL allows multiple NULL values in a UNIQUE index, so deleted documents
        //    are automatically exempt from the uniqueness constraint below.
        DB::statement(
            'ALTER TABLE documents ADD COLUMN live_sequence INT UNSIGNED GENERATED ALWAYS AS'
            . ' (IF(deleted_at IS NULL, sequence_number, NULL)) VIRTUAL'
        );

        // 3. New uniqueness guarantee: no two active documents may share
        //    (company, type, year, sequence_number). Deleted rows have live_sequence = NULL
        //    and are exempt (multiple NULLs are allowed in MySQL UNIQUE indexes).
        //    Added BEFORE dropping the old constraint because MySQL requires at least
        //    one index covering company_id to support the documents.company_id FK.
        $newConstraintExists = ! empty(DB::select(
            "SHOW INDEX FROM documents WHERE Key_name = 'doc_active_seq_unique'"
        ));
        if (! $newConstraintExists) {
            DB::statement(
                'ALTER TABLE documents ADD UNIQUE KEY doc_active_seq_unique'
                . ' (company_id, document_type_id, document_year, live_sequence)'
            );
        }

        // 4. Drop the old (company_id, document_number) unique constraint now that
        //    doc_active_seq_unique covers company_id and satisfies the FK requirement.
        $oldConstraintExists = ! empty(DB::select(
            "SHOW INDEX FROM documents WHERE Key_name = 'documents_company_document_number_unique'"
        ));
        if ($oldConstraintExists) {
            DB::statement('ALTER TABLE documents DROP INDEX documents_company_document_number_unique');
        }

        // 5. Backfill document_year and sequence_number for existing active documents
        //    by parsing them from the stored document_number string (PREFIX-YEAR-NNNN).
        DB::statement(
            "UPDATE documents
             SET
                 document_year   = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(document_number, '-', 2), '-', -1) AS UNSIGNED),
                 sequence_number = CAST(SUBSTRING_INDEX(document_number, '-', -1) AS UNSIGNED)
             WHERE deleted_at IS NULL
               AND sequence_number IS NULL
               AND document_number REGEXP '^[A-Za-z]+-[0-9]{4}-[0-9]+\$'"
        );
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE documents DROP INDEX doc_active_seq_unique');
        } catch (\Throwable) {}

        try {
            DB::statement('ALTER TABLE documents DROP COLUMN live_sequence');
        } catch (\Throwable) {}

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['sequence_number', 'document_year']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unique(['company_id', 'document_number'], 'documents_company_document_number_unique');
        });
    }
};
