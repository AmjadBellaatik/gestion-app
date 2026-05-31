<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'repair_tickets',

            function (
                Blueprint $table
            ) {

                /*
                |--------------------------------------------------------------------------
                | Motorcycle Unit
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'repair_tickets',
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

                /*
                |--------------------------------------------------------------------------
                | Mileage
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'repair_tickets',
                        'mileage'
                    )

                ) {

                    $table->integer(
                        'mileage'
                    )

                        ->default(0);
                }

                /*
                |--------------------------------------------------------------------------
                | Repair Type
                |--------------------------------------------------------------------------
                */

                if (

                    ! Schema::hasColumn(
                        'repair_tickets',
                        'repair_type'
                    )

                ) {

                    $table->string(
                        'repair_type'
                    )

                        ->default('paid');
                }
            }
        );
    }

    public function down(): void
    {
        //
    }
};