<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (
            Blueprint $table
        ) {

            if (! Schema::hasColumn('users', 'phone')) {

                $table->string('phone')
                    ->nullable()
                    ->after('email');

            }

            if (! Schema::hasColumn('users', 'address')) {

                $table->text('address')
                    ->nullable()
                    ->after('phone');

            }

            if (! Schema::hasColumn('users', 'profile_picture')) {

                $table->string('profile_picture')
                    ->nullable()
                    ->after('address');

            }

            if (! Schema::hasColumn('users', 'status')) {

                $table->boolean('status')
                    ->default(true)
                    ->after('language');

            }

            if (! Schema::hasColumn('users', 'last_login_at')) {

                $table->timestamp('last_login_at')
                    ->nullable()
                    ->after('status');

            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (
            Blueprint $table
        ) {

            $columns = [];

            foreach ([

                'phone',
                'address',
                'profile_picture',
                'status',
                'last_login_at',

            ] as $column) {

                if (

                    Schema::hasColumn(
                        'users',
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