<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'stock_movements',

            function (
                Blueprint $table
            ) {

                if (

                    ! Schema::hasColumn(
                        'stock_movements',
                        'product_id'
                    )

                ) {

                    $table->foreignId(
                        'product_id'
                    )

                        ->nullable()

                        ->constrained()

                        ->nullOnDelete();

                } else {

                    // Column exists but may have been created NOT NULL — ensure it is nullable.
                    \Illuminate\Support\Facades\DB::statement(
                        'ALTER TABLE `stock_movements` MODIFY `product_id` BIGINT UNSIGNED NULL'
                    );

                }

                if (

                    ! Schema::hasColumn(
                        'stock_movements',
                        'motorcycle_unit_id'
                    )

                ) {

                    $table->foreignId(
                        'motorcycle_unit_id'
                    )

                        ->nullable()

                        ->constrained()

                        ->nullOnDelete();
                }
            }
        );
    }

    public function down(): void
    {
        //
    }
};