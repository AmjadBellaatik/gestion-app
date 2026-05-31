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
        Schema::create('sales', function (
            Blueprint $table
        ) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */

            $table->foreignId('company_id')

                ->constrained()

                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            $table->foreignId('client_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->foreignId('reseller_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->foreignId('user_id')

                ->constrained()

                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Sale Information
            |--------------------------------------------------------------------------
            */

            $table->string('sale_number')

                ->unique();

            $table->string('sale_type')

                ->default('normal');

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'subtotal',
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

            $table->decimal(
                'paid_amount',
                15,
                2
            )->default(0);

            $table->decimal(
                'remaining_amount',
                15,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->string('payment_status')

                ->default('unpaid');

            $table->string('status')

                ->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text('notes')

                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Soft Deletes
            |--------------------------------------------------------------------------
            */

            $table->softDeletes();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};