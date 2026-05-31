<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_items', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId(
                'repair_ticket_id'
            )

                ->constrained()

                ->cascadeOnDelete();

            $table->foreignId(
                'product_id'
            )

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->decimal(
                'quantity',
                15,
                2
            )->default(1);

            $table->decimal(
                'unit_price',
                15,
                2
            )->default(0);

            $table->decimal(
                'total',
                15,
                2
            )->default(0);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'repair_items'
        );
    }
};