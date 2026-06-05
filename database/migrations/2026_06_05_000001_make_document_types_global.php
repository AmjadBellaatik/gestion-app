<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the foreign key first (MySQL requires FK gone before its index)
        //    Guard against servers where the FK was never created.
        $database = DB::connection()->getDatabaseName();
        $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'document_types')
            ->where('COLUMN_NAME', 'company_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if ($fkExists) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
        }

        // 2. Drop the composite unique constraint (company_id, code) if it exists
        $indexExists = collect(DB::select("SHOW INDEX FROM `document_types`"))
            ->contains('Key_name', 'document_types_company_id_code_unique');

        if ($indexExists) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->dropUnique('document_types_company_id_code_unique');
            });
        }

        // 3. Make company_id nullable
        Schema::table('document_types', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

        // 3. Merge all document types to global — keep only the unique codes
        //    from the company that has the most types, delete the duplicates.
        $rows = DB::table('document_types')
            ->orderBy('company_id')
            ->orderBy('id')
            ->get()
            ->groupBy('code');

        $keepIds = [];
        foreach ($rows as $group) {
            // Keep the first (lowest id) row for each code, discard duplicates
            $keepIds[] = $group->first()->id;
        }

        DB::table('document_types')
            ->whereNotIn('id', $keepIds)
            ->delete();

        // 4. Set all remaining rows to global (company_id = NULL)
        DB::table('document_types')->update(['company_id' => null]);

        // 5. Add unique constraint on code alone
        Schema::table('document_types', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'code']);
        });
    }
};
