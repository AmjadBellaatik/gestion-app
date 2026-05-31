<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (
            Blueprint $table
        ) {

            $columns = [

                'chassis_number',

                'mileage',

                'condition',

            ];

            foreach ($columns as $column) {

                if (

                    Schema::hasColumn(
                        'products',
                        $column
                    )

                ) {

                    $table->dropColumn(
                        $column
                    );
                }
            }
        });
    }

    public function down(): void
    {
        //
    }
};