<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_items', function (Blueprint $table) {

            $table->string('item_type')
                ->default('part')
                ->after('product_id');

            $table->text('item_description')
                ->nullable()
                ->after('total');

            $table->decimal('discount_amount', 15, 2)
                ->default(0)
                ->after('item_description');

        });
    }

    public function down(): void
    {
        Schema::table('repair_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'item_description', 'discount_amount']);
        });
    }
};
