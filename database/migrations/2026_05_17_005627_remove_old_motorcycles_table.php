<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Stock Movements
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasColumn(
                'stock_movements',
                'motorcycle_id'
            )

        ) {

            Schema::table(
                'stock_movements',

                function (
                    Blueprint $table
                ) {

                    $table->dropForeign([
                        'motorcycle_id',
                    ]);

                    $table->dropColumn(
                        'motorcycle_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Warranty Contracts
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasColumn(
                'warranty_contracts',
                'motorcycle_id'
            )

        ) {

            Schema::table(
                'warranty_contracts',

                function (
                    Blueprint $table
                ) {

                    $table->dropForeign([
                        'motorcycle_id',
                    ]);

                    $table->dropColumn(
                        'motorcycle_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Repair Tickets
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasColumn(
                'repair_tickets',
                'motorcycle_id'
            )

        ) {

            Schema::table(
                'repair_tickets',

                function (
                    Blueprint $table
                ) {

                    $table->dropForeign([
                        'motorcycle_id',
                    ]);

                    $table->dropColumn(
                        'motorcycle_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Documents
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasColumn(
                'documents',
                'motorcycle_id'
            )

        ) {

            Schema::table(
                'documents',

                function (
                    Blueprint $table
                ) {

                    $table->dropForeign([
                        'motorcycle_id',
                    ]);

                    $table->dropColumn(
                        'motorcycle_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Document Items
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasColumn(
                'document_items',
                'motorcycle_id'
            )

        ) {

            Schema::table(
                'document_items',

                function (
                    Blueprint $table
                ) {

                    $table->dropForeign([
                        'motorcycle_id',
                    ]);

                    $table->dropColumn(
                        'motorcycle_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Conformity Certificates
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasColumn(
                'conformity_certificates',
                'motorcycle_id'
            )

        ) {

            Schema::table(
                'conformity_certificates',

                function (
                    Blueprint $table
                ) {

                    $table->dropForeign([
                        'motorcycle_id',
                    ]);

                    $table->dropColumn(
                        'motorcycle_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sale Items
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasColumn(
                'sale_items',
                'motorcycle_id'
            )

        ) {

            Schema::table(
                'sale_items',

                function (
                    Blueprint $table
                ) {

                    $table->dropForeign([
                        'motorcycle_id',
                    ]);

                    $table->dropColumn(
                        'motorcycle_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Drop Old Table
        |--------------------------------------------------------------------------
        */

        if (

            Schema::hasTable(
                'motorcycles'
            )

        ) {

            Schema::drop(
                'motorcycles'
            );
        }
    }

    public function down(): void
    {
        //
    }
};