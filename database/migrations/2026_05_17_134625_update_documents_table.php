<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'documents',

            function (
                Blueprint $table
            ) {

                if (

                    ! Schema::hasColumn(
                        'documents',
                        'uuid'
                    )

                ) {

                    $table->uuid(
                        'uuid'
                    )

                        ->nullable()

                        ->unique()

                        ->after('id');
                }

                if (

                    ! Schema::hasColumn(
                        'documents',
                        'verification_url'
                    )

                ) {

                    $table->string(
                        'verification_url'
                    )

                        ->nullable()

                        ->after('uuid');
                }

                if (

                    ! Schema::hasColumn(
                        'documents',
                        'document_number'
                    )

                ) {

                    $table->string(
                        'document_number'
                    )

                        ->nullable()

                        ->unique();
                }
            }
        );
    }

    public function down(): void
    {
        //
    }
};