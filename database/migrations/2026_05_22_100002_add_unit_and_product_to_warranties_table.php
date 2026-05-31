<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranties', function (Blueprint $table) {
            // Link directly to the specific motorcycle unit (VIN-tracked entity)
            if (! Schema::hasColumn('warranties', 'motorcycle_unit_id')) {
                $table->foreignId('motorcycle_unit_id')
                    ->nullable()
                    ->after('motorcycle_id')
                    ->constrained('motorcycle_units')
                    ->nullOnDelete();
            }

            // Link to any non-motorcycle product (accessories, bikes, trottinettes…)
            if (! Schema::hasColumn('warranties', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->after('motorcycle_unit_id')
                    ->constrained('products')
                    ->nullOnDelete();
            }

            // Kilometre limit for the warranty
            if (! Schema::hasColumn('warranties', 'warranty_kilometers')) {
                $table->unsignedInteger('warranty_kilometers')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warranties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('motorcycle_unit_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('warranty_kilometers');
        });
    }
};
