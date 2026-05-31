<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')

                ->constrained()

                ->cascadeOnDelete();

            $table->string('type');

            $table->string('category')

                ->nullable();

            $table->decimal(
                'amount',
                15,
                2
            );

            $table->string('direction')

                ->default('income');

            $table->string('reference_type')

                ->nullable();

            $table->unsignedBigInteger(
                'reference_id'
            )->nullable();

            $table->string('payment_method')

                ->nullable();

            $table->string('status')

                ->default('validated');

            $table->text('description')

                ->nullable();

            $table->foreignId('created_by')

                ->nullable()

                ->constrained('users')

                ->nullOnDelete();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'transactions'
        );
    }
};