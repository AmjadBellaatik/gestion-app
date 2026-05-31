<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claims', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('warranty_contract_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('repair_ticket_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('motorcycle_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('claim_number')
                ->unique();

            $table->date('claim_date');

            $table->string('status')
                ->default('pending');

            $table->longText('issue_description');

            $table->longText('diagnosis')
                ->nullable();

            $table->decimal(
                'claimed_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'approved_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'reimbursed_amount',
                12,
                2
            )->default(0);

            $table->boolean('approved')
                ->default(false);

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamp('reimbursed_at')
                ->nullable();

            $table->longText('notes')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'warranty_claims'
        );
    }
};