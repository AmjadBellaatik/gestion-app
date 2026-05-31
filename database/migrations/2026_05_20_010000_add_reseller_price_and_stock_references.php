<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'reseller_price')) {
                $table->decimal('reseller_price', 12, 2)
                    ->default(0)
                    ->after('selling_price');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            if (! Schema::hasColumn('stock_movements', 'reference_type')) {
                $table->string('reference_type')
                    ->nullable()
                    ->after('reference');
            }

            if (! Schema::hasColumn('stock_movements', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')
                    ->nullable()
                    ->after('reference_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_movements', 'reference_id')) {
                $table->dropColumn('reference_id');
            }

            if (Schema::hasColumn('stock_movements', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'reseller_price')) {
                $table->dropColumn('reseller_price');
            }
        });
    }
};
