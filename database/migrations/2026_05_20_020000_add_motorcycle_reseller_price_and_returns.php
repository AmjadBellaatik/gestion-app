<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table): void {
            if (! Schema::hasColumn('motorcycle_models', 'reseller_price')) {
                $table->decimal('reseller_price', 12, 2)
                    ->default(0)
                    ->after('price_ttc');
            }
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('sale_items', 'returned_quantity')) {
                $table->decimal('returned_quantity', 12, 2)
                    ->default(0)
                    ->after('quantity');
            }
        });

        Schema::table('sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales', 'returned_at')) {
                $table->timestamp('returned_at')
                    ->nullable()
                    ->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            if (Schema::hasColumn('sales', 'returned_at')) {
                $table->dropColumn('returned_at');
            }
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            if (Schema::hasColumn('sale_items', 'returned_quantity')) {
                $table->dropColumn('returned_quantity');
            }
        });

        Schema::table('motorcycle_models', function (Blueprint $table): void {
            if (Schema::hasColumn('motorcycle_models', 'reseller_price')) {
                $table->dropColumn('reseller_price');
            }
        });
    }
};
