<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'warehouses',

            function (
                Blueprint $table
            ) {

                if (

                    ! Schema::hasColumn(
                        'warehouses',
                        'company_id'
                    )

                ) {

                    $table->foreignId(
                        'company_id'
                    )

                        ->nullable()

                        ->after('id')

                        ->constrained()

                        ->cascadeOnDelete();
                }

                if (

                    ! Schema::hasColumn(
                        'warehouses',
                        'code'
                    )

                ) {

                    $table->string(
                        'code'
                    )

                        ->nullable();
                }

                if (

                    ! Schema::hasColumn(
                        'warehouses',
                        'address'
                    )

                ) {

                    $table->text(
                        'address'
                    )

                        ->nullable();
                }

                if (

                    ! Schema::hasColumn(
                        'warehouses',
                        'phone'
                    )

                ) {

                    $table->string(
                        'phone'
                    )

                        ->nullable();
                }

                if (

                    ! Schema::hasColumn(
                        'warehouses',
                        'is_active'
                    )

                ) {

                    $table->boolean(
                        'is_active'
                    )

                        ->default(true);
                }
            }
        );
    }

    public function down(): void
    {
        //
    }
};