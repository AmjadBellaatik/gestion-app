<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'motorcycle_unit_id')) {
                $table->foreignId('motorcycle_unit_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'motorcycle_unit_id')) {
                $table->dropConstrainedForeignId('motorcycle_unit_id');
            }
        });
    }
};

