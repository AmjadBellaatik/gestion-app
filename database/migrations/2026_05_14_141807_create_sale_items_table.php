<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_items', function (
            Blueprint $table
        ) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            $table->foreignId('sale_id')

                ->constrained()

                ->cascadeOnDelete();

            $table->foreignId('product_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->foreignId('motorcycle_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Quantities
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'quantity',
                15,
                2
            )->default(1);

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'unit_price',
                15,
                2
            )->default(0);

            $table->decimal(
                'discount',
                15,
                2
            )->default(0);

            $table->decimal(
                'tax',
                15,
                2
            )->default(0);

            $table->decimal(
                'total',
                15,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};