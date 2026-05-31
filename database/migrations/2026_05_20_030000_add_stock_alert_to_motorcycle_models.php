<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table): void {
            if (! Schema::hasColumn('motorcycle_models', 'stock_alert')) {
                $table->unsignedInteger('stock_alert')
                    ->default(0)
                    ->after('reseller_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table): void {
            if (Schema::hasColumn('motorcycle_models', 'stock_alert')) {
                $table->dropColumn('stock_alert');
            }
        });
    }
};
