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

            if (! Schema::hasColumn('brands', 'footer')) {
                $table->longText('footer')->nullable()->after('director_signature');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'has_warranty')) {
                $table->boolean('has_warranty')->default(false)->after('serial_required');
            }

            if (! Schema::hasColumn('products', 'status')) {
                $table->string('status')->default('in_stock')->after(
                    Schema::hasColumn('products', 'has_warranty') ? 'has_warranty' : 'stock_alert'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('brands', function (Blueprint $table): void {
            if (Schema::hasColumn('brands', 'footer')) {
                $table->dropColumn('footer');
            }

            if (Schema::hasColumn('brands', 'accreditation_reference')) {
                $table->dropColumn('accreditation_reference');
            }
        });
    }
};
