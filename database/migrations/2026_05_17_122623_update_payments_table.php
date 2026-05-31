<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'payments',

            function (
                Blueprint $table
            ) {

                if (

                    ! Schema::hasColumn(
                        'payments',
                        'method'
                    )

                ) {

                    $table->string(
                        'method'
                    )

                        ->default('cash')

                        ->after('amount');
                }

                if (

                    ! Schema::hasColumn(
                        'payments',
                        'reference'
                    )

                ) {

                    $table->string(
                        'reference'
                    )

                        ->nullable()

                        ->after('method');
                }

                if (

                    ! Schema::hasColumn(
                        'payments',
                        'status'
                    )

                ) {

                    $table->string(
                        'status'
                    )

                        ->default('paid')

                        ->after('reference');
                }
            }
        );
    }

    public function down(): void
    {
        //
    }
};