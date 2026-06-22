<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks tickets created under the new SOURCE-DRIVEN repair logic.
     *
     * Backward compatibility: every pre-existing repair keeps the default
     * `false` and is therefore NEVER auto-converted (type / financial / stock
     * behaviour preserved). Only tickets created after this update opt in to
     * the source-driven rules and have this flag set to true on creation.
     */
    public function up(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('repair_tickets', 'is_source_driven')) {
                $table->boolean('is_source_driven')
                    ->default(false)
                    ->after('repair_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('repair_tickets', 'is_source_driven')) {
                $table->dropColumn('is_source_driven');
            }
        });
    }
};
