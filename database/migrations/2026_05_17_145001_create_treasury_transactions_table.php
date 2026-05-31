<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'treasury_transactions',

            function (
                Blueprint $table
            ) {

                $table->id();

                $table->foreignId(
                    'company_id'
                )

                    ->constrained()

                    ->cascadeOnDelete();

                $table->foreignId(
                    'cash_register_id'
                )

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                $table->foreignId(
                    'payment_id'
                )

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                $table->string(
                    'type'
                );

                $table->decimal(
                    'amount',
                    12,
                    2
                );

                $table->text(
                    'description'
                )

                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'treasury_transactions'
        );
    }
};