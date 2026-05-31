<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'cheque_payments',

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
                    'cheque_number'
                );

                $table->string(
                    'bank_name'
                );

                $table->date(
                    'due_date'
                );

                $table->string(
                    'scan_path'
                )

                    ->nullable();

                $table->string(
                    'status'
                )

                    ->default('pending');

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'cheque_payments'
        );
    }
};