<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('document_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('reseller_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('document_number')
                ->unique();

            $table->date('document_date');

            $table->decimal(
                'subtotal',
                12,
                2
            )->default(0);

            $table->decimal(
                'tax_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'discount_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'total_amount',
                12,
                2
            )->default(0);

            $table->string('status')
                ->default('draft');

            $table->text('notes')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'documents'
        );
    }
};