<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (
            Blueprint $table
        ) {

            if (
                Schema::hasColumn(
                    'clients',
                    'city'
                )
            ) {

                $table->dropColumn(
                    'city'
                );
            }

            if (
                Schema::hasColumn(
                    'clients',
                    'country'
                )
            ) {

                $table->dropColumn(
                    'country'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (
            Blueprint $table
        ) {

            $table->string(
                'city'
            )

                ->nullable();

            $table->string(
                'country'
            )

                ->nullable();
        });
    }
};