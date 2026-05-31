<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'bank_transfer_payments',

            function (
                Blueprint $table
            ) {

                $table->id();

                $table->foreignId(
                    'payment_id'
                )

                    ->constrained()

                    ->cascadeOnDelete();

                $table->string(
                    'bank_name'
                );

                $table->string(
                    'reference_number'
                );

                $table->date(
                    'transfer_date'
                );

                $table->string(
                    'confirmation_file'
                )

                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'bank_transfer_payments'
        );
    }
};