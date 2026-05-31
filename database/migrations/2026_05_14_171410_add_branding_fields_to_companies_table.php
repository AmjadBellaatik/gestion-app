<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (
            Blueprint $table
        ) {

            if (

                ! Schema::hasColumn(
                    'companies',
                    'logo'
                )

            ) {

                $table->string('logo')

                    ->nullable()

                    ->after('name');

            }

            if (

                ! Schema::hasColumn(
                    'companies',
                    'stamp'
                )

            ) {

                $table->string('stamp')

                    ->nullable()

                    ->after('logo');

            }

            if (

                ! Schema::hasColumn(
                    'companies',
                    'signature'
                )

            ) {

                $table->string('signature')

                    ->nullable()

                    ->after('stamp');

            }

            if (

                ! Schema::hasColumn(
                    'companies',
                    'footer'
                )

            ) {

                $table->longText('footer')

                    ->nullable()

                    ->after('signature');

            }

            if (

                ! Schema::hasColumn(
                    'companies',
                    'ice'
                )

            ) {

                $table->string('ice')

                    ->nullable()

                    ->after('footer');

            }

            if (

                ! Schema::hasColumn(
                    'companies',
                    'rc'
                )

            ) {

                $table->string('rc')

                    ->nullable()

                    ->after('ice');

            }

            if (

                ! Schema::hasColumn(
                    'companies',
                    'if'
                )

            ) {

                $table->string('if')

                    ->nullable()

                    ->after('rc');

            }

        });
    }

    public function down(): void
    {
        Schema::table('companies', function (
            Blueprint $table
        ) {

            $columns = [];

            foreach ([

                'logo',
                'stamp',
                'signature',
                'footer',
                'ice',
                'rc',
                'if',

            ] as $column) {

                if (

                    Schema::hasColumn(
                        'companies',
                        $column
                    )

                ) {

                    $columns[] = $column;

                }

            }

            if (! empty($columns)) {

                $table->dropColumn(
                    $columns
                );

            }

        });
    }
};