<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'motorcycle_units',

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
                    'warehouse_id'
                )

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                $table->foreignId(
                    'motorcycle_model_id'
                )

                    ->constrained()

                    ->cascadeOnDelete();

                $table->foreignId(
                    'client_id'
                )

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                $table->foreignId(
                    'document_id'
                )

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Identification
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'chassis_number'
                )

                    ->unique();

                $table->string(
                    'fabrication_number'
                )

                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status'
                )

                    ->default('in_stock');

                /*
                |--------------------------------------------------------------------------
                | Mileage
                |--------------------------------------------------------------------------
                */

                $table->integer(
                    'mileage'
                )

                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'purchase_date'
                )

                    ->nullable();

                $table->date(
                    'sale_date'
                )

                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'motorcycle_units'
        );
    }
};