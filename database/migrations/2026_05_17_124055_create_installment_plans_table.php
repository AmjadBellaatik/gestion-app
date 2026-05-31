<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'installment_plans',

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
                    'client_id'
                )

                    ->constrained()

                    ->cascadeOnDelete();

                $table->foreignId(
                    'document_id'
                )

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                $table->decimal(
                    'total_amount',
                    12,
                    2
                );

                $table->decimal(
                    'paid_amount',
                    12,
                    2
                )

                    ->default(0);

                $table->decimal(
                    'remaining_amount',
                    12,
                    2
                );

                $table->integer(
                    'months'
                );

                $table->date(
                    'start_date'
                );

                $table->string(
                    'status'
                )

                    ->default('active');

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'installment_plans'
        );
    }
};