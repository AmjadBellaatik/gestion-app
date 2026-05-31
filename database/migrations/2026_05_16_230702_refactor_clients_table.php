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

            /*
            |--------------------------------------------------------------------------
            | Type
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'clients',
                'type'
            )) {

                $table->string(
                    'type'
                )

                    ->default('person')

                    ->after('company_id');
            }

            /*
            |--------------------------------------------------------------------------
            | City
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'clients',
                'city'
            )) {

                $table->string(
                    'city'
                )

                    ->nullable()

                    ->after('address');
            }

            /*
            |--------------------------------------------------------------------------
            | Country
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'clients',
                'country'
            )) {

                $table->string(
                    'country'
                )

                    ->nullable()

                    ->after('city');
            }

            /*
            |--------------------------------------------------------------------------
            | Active Status
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'clients',
                'is_active'
            )) {

                $table->boolean(
                    'is_active'
                )

                    ->default(true)

                    ->after('country');
            }

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'clients',
                'notes'
            )) {

                $table->text(
                    'notes'
                )

                    ->nullable()

                    ->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (
            Blueprint $table
        ) {

            $columns = [

                'type',

                'city',

                'country',

                'is_active',

                'notes',

            ];

            foreach ($columns as $column) {

                if (Schema::hasColumn(
                    'clients',
                    $column
                )) {

                    $table->dropColumn(
                        $column
                    );
                }
            }
        });
    }
};