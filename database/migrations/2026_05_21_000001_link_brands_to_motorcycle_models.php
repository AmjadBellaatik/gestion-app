<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            if (! Schema::hasColumn('brands', 'accreditation_reference')) {
                $table->string('accreditation_reference')->nullable()->after('name');
            }
        });

        Schema::table('motorcycle_models', function (Blueprint $table): void {
            if (! Schema::hasColumn('motorcycle_models', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('stock_alert')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table): void {
            if (Schema::hasColumn('motorcycle_models', 'brand_id')) {
                $table->dropConstrainedForeignId('brand_id');
            }
        });

        Schema::table('brands', function (Blueprint $table): void {
            if (Schema::hasColumn('brands', 'accreditation_reference')) {
                $table->dropColumn('accreditation_reference');
            }
        });
    }
};
