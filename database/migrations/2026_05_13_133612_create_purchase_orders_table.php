<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('order_number')
                ->unique();

            $table->date('order_date');

            $table->string('status')
                ->default('draft');

            $table->decimal(
                'subtotal',
                12,
                2
            )->default(0);

            $table->decimal(
                'tax_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'discount_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'total_amount',
                12,
                2
            )->default(0);

            $table->text('notes')
                ->nullable();

            $table->timestamp('received_at')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'purchase_orders'
        );
    }
};