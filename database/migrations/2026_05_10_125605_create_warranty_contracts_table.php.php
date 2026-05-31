<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_contracts', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('motorcycle_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('document_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('contract_number')
                ->unique();

            $table->date('delivery_date');

            $table->date('expiration_date');

            $table->integer('mileage_limit')
                ->nullable();

            $table->integer('current_mileage')
                ->nullable();

            $table->longText('warranty_terms')
                ->nullable();

            $table->longText('warranty_exclusions')
                ->nullable();

            $table->boolean('customer_signed')
                ->default(false);

            $table->timestamp('customer_signed_at')
                ->nullable();

            $table->boolean('seller_signed')
                ->default(false);

            $table->timestamp('seller_signed_at')
                ->nullable();

            $table->string('customer_signature')
                ->nullable();

            $table->string('seller_signature')
                ->nullable();

            $table->string('status')
                ->default('active');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'warranty_contracts'
        );
    }
};