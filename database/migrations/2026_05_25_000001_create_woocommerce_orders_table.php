<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('woocommerce_orders', function (Blueprint $table) {
            $table->id();

            // WooCommerce identifiers
            $table->unsignedBigInteger('wc_order_id')->unique();
            $table->string('wc_order_number')->nullable(); // e.g. "#1023"
            $table->string('status', 50)->default('pending'); // pending, processing, completed, cancelled, refunded, failed, on-hold

            // Customer
            $table->string('customer_first_name')->nullable();
            $table->string('customer_last_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 50)->nullable();

            // Addresses (full objects as JSON)
            $table->json('billing')->nullable();
            $table->json('shipping')->nullable();

            // Order lines & totals
            $table->json('line_items');                       // array of {name, qty, subtotal, total, sku}
            $table->json('shipping_lines')->nullable();       // shipping method + cost
            $table->json('fee_lines')->nullable();            // extra fees if any
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 10)->default('MAD');

            // Payment
            $table->string('payment_method', 80)->nullable();       // e.g. "bacs"
            $table->string('payment_method_title')->nullable();      // e.g. "Bank Transfer"
            $table->boolean('paid')->default(false);

            // Misc
            $table->text('customer_note')->nullable();

            // Full raw payload for reference
            $table->json('raw_payload');

            // WooCommerce order date
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_orders');
    }
};
