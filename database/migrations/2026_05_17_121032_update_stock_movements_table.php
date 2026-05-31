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

                /*
                |--------------------------------------------------------------------------
                | Warehouse
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'stock_movements',
                        'warehouse_id'
                    )

                ) {

                    $table->foreignId(
                        'warehouse_id'
                    )

                        ->nullable()

                        ->after('company_id')

                        ->constrained()

                        ->nullOnDelete();
                }

                /*
                |--------------------------------------------------------------------------
                | Reference
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'stock_movements',
                        'reference'
                    )

                ) {

                    $table->string(
                        'reference'
                    )

                        ->nullable();
                }

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'stock_movements',
                        'notes'
                    )

                ) {

                    $table->text(
                        'notes'
                    )

                        ->nullable();
                }

                /*
                |--------------------------------------------------------------------------
                | Type
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'stock_movements',
                        'type'
                    )

                ) {

                    $table->string(
                        'type'
                    )

                        ->default('in');
                }

                /*
                |--------------------------------------------------------------------------
                | Quantity
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'stock_movements',
                        'quantity'
                    )

                ) {

                    $table->decimal(
                        'quantity',
                        12,
                        2
                    )

                        ->default(0);
                }
            }
        );
    }

    public function down(): void
    {
        //
    }
};