<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any existing single-column unique index on variante before adding
        // the composite one — old code had a per-column unique rule enforced only
        // at the application layer, so there should be no DB-level index to drop.

        // Deduplicate existing rows before adding the constraint:
        // Keep the lowest id for each (type, variante) combination.
        DB::statement("
            DELETE mm1
            FROM motorcycle_models mm1
            INNER JOIN motorcycle_models mm2
                ON mm1.type     = mm2.type
                AND mm1.variante = mm2.variante
                AND mm1.id       > mm2.id
            WHERE mm1.type IS NOT NULL
              AND mm1.variante IS NOT NULL
        ");

        Schema::table('motorcycle_models', function (Blueprint $table) {
            // Composite unique: same (type, variante) pair cannot appear twice.
            // Both columns are nullable — MySQL ignores NULLs in unique indexes,
            // so rows with NULL type or NULL variante are still allowed freely.
            $table->unique(['type', 'variante'], 'motorcycle_models_type_variante_unique');
        });
    }

    public function down(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table) {
            $table->dropUnique('motorcycle_models_type_variante_unique');
        });
    }
};
