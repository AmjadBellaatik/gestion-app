<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'warranty_duration_value')) {
                $table->unsignedInteger('warranty_duration_value')->nullable()->after('total');
            }

            if (! Schema::hasColumn('sale_items', 'warranty_duration_unit')) {
                $table->string('warranty_duration_unit', 20)->nullable()->after('warranty_duration_value');
            }

            if (! Schema::hasColumn('sale_items', 'warranty_kilometers')) {
                $table->unsignedInteger('warranty_kilometers')->nullable()->after('warranty_duration_unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'warranty_kilometers')) {
                $table->dropColumn('warranty_kilometers');
            }

            if (Schema::hasColumn('sale_items', 'warranty_duration_unit')) {
                $table->dropColumn('warranty_duration_unit');
            }

            if (Schema::hasColumn('sale_items', 'warranty_duration_value')) {
                $table->dropColumn('warranty_duration_value');
            }
        });
    }
};
