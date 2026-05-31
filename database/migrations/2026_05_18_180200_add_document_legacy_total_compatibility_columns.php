<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('documents', 'total')) {
                $table->decimal('total', 15, 2)->default(0)->after('total_amount');
            }
        });

        Schema::table('document_items', function (Blueprint $table) {
            if (! Schema::hasColumn('document_items', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('tax_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'tax')) {
                $table->dropColumn('tax');
            }

            if (Schema::hasColumn('documents', 'total')) {
                $table->dropColumn('total');
            }
        });

        Schema::table('document_items', function (Blueprint $table) {
            if (Schema::hasColumn('document_items', 'tax')) {
                $table->dropColumn('tax');
            }
        });
    }
};
