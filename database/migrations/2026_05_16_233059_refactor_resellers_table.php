<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (
            Blueprint $table
        ) {

            if (! Schema::hasColumn(
                'resellers',
                'city'
            )) {

                $table->string(
                    'city'
                )

                    ->nullable()

                    ->after('address');
            }

            if (! Schema::hasColumn(
                'resellers',
                'country'
            )) {

                $table->string(
                    'country'
                )

                    ->nullable()

                    ->after('city');
            }

            if (! Schema::hasColumn(
                'resellers',
                'ice'
            )) {

                $table->string(
                    'ice'
                )

                    ->nullable()

                    ->after('country');
            }

            if (! Schema::hasColumn(
                'resellers',
                'rc'
            )) {

                $table->string(
                    'rc'
                )

                    ->nullable()

                    ->after('ice');
            }

            if (! Schema::hasColumn(
                'resellers',
                'if'
            )) {

                $table->string(
                    'if'
                )

                    ->nullable()

                    ->after('rc');
            }

            if (! Schema::hasColumn(
                'resellers',
                'patente'
            )) {

                $table->string(
                    'patente'
                )

                    ->nullable()

                    ->after('if');
            }

            if (! Schema::hasColumn(
                'resellers',
                'representative_name'
            )) {

                $table->string(
                    'representative_name'
                )

                    ->nullable()

                    ->after('patente');
            }

            if (! Schema::hasColumn(
                'resellers',
                'max_debt'
            )) {

                $table->decimal(
                    'max_debt',
                    15,
                    2
                )

                    ->default(
                        200000
                    )

                    ->after('current_debt');
            }

            if (! Schema::hasColumn(
                'resellers',
                'credit_days'
            )) {

                $table->integer(
                    'credit_days'
                )

                    ->default(30)

                    ->after('max_debt');
            }

            if (! Schema::hasColumn(
                'resellers',
                'is_blocked'
            )) {

                $table->boolean(
                    'is_blocked'
                )

                    ->default(false)

                    ->after('credit_days');
            }

            if (! Schema::hasColumn(
                'resellers',
                'blocked_reason'
            )) {

                $table->text(
                    'blocked_reason'
                )

                    ->nullable()

                    ->after('is_blocked');
            }

            if (! Schema::hasColumn(
                'resellers',
                'notes'
            )) {

                $table->text(
                    'notes'
                )

                    ->nullable()

                    ->after('blocked_reason');
            }

            if (! Schema::hasColumn(
                'resellers',
                'is_active'
            )) {

                $table->boolean(
                    'is_active'
                )

                    ->default(true)

                    ->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (
            Blueprint $table
        ) {

            $table->dropColumn([

                'city',

                'country',

                'ice',

                'rc',

                'if',

                'patente',

                'representative_name',

                'max_debt',

                'credit_days',

                'is_blocked',

                'blocked_reason',

                'notes',

                'is_active',

            ]);
        });
    }
};