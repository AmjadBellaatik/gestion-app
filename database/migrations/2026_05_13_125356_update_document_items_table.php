<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_items', function (
            Blueprint $table
        ) {

            if (! Schema::hasColumn('document_items', 'serial_number')) {

                $table->string('serial_number')
                    ->nullable();

            }

            if (! Schema::hasColumn('document_items', 'warranty_months')) {

                $table->integer('warranty_months')
                    ->nullable();

            }

            if (! Schema::hasColumn('document_items', 'warehouse_id')) {

                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

            }

            if (! Schema::hasColumn('document_items', 'discount_amount')) {

                $table->decimal(
                    'discount_amount',
                    12,
                    2
                )->default(0);

            }

            if (! Schema::hasColumn('document_items', 'tax_amount')) {

                $table->decimal(
                    'tax_amount',
                    12,
                    2
                )->default(0);

            }

            if (! Schema::hasColumn('document_items', 'line_notes')) {

                $table->longText('line_notes')
                    ->nullable();

            }

            if (! Schema::hasColumn('document_items', 'line_sort')) {

                $table->integer('line_sort')
                    ->default(0);

            }

            if (! Schema::hasColumn('document_items', 'unit_type')) {

                $table->string('unit_type')
                    ->default('unit');

            }

        });
    }

    public function down(): void
    {
        //
    }
};