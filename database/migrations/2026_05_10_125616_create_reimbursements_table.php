<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')

                ->constrained()

                ->cascadeOnDelete();

            $table->foreignId('repair_ticket_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->foreignId('supplier_id')

                ->nullable()

                ->constrained('clients')

                ->nullOnDelete();

            $table->decimal(
                'amount',
                15,
                2
            )->default(0);

            $table->string('status')

                ->default('pending');

            $table->longText('notes')

                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'reimbursements'
        );
    }
};