<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(

            'document_types',

            function (
                Blueprint $table
            ) {

                if (
                    ! Schema::hasColumn(
                        'document_types',
                        'next_number'
                    )
                ) {

                    $table->integer(
                        'next_number'
                    )->default(1);

                }

                if (
                    ! Schema::hasColumn(
                        'document_types',
                        'language'
                    )
                ) {

                    $table->string(
                        'language'
                    )->default('fr');

                }

            }

        );
    }

    public function down(): void
    {
        Schema::table(

            'document_types',

            function (
                Blueprint $table
            ) {

                $table->dropColumn([

                    'next_number',
                    'language',

                ]);

            }

        );
    }
};