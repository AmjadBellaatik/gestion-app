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

            if (! Schema::hasColumn(
                'companies',
                'logo'
            )) {

                $table->string(
                    'logo'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'phone'
            )) {

                $table->string(
                    'phone'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'email'
            )) {

                $table->string(
                    'email'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'website'
            )) {

                $table->string(
                    'website'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'ice'
            )) {

                $table->string(
                    'ice'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'rc'
            )) {

                $table->string(
                    'rc'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'tax_number'
            )) {

                $table->string(
                    'tax_number'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'address'
            )) {

                $table->text(
                    'address'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'companies',
                'invoice_footer'
            )) {

                $table->text(
                    'invoice_footer'
                )

                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (
            Blueprint $table
        ) {

            $table->dropColumn([

                'logo',
                'phone',
                'email',
                'website',

                'ice',
                'rc',
                'tax_number',

                'address',
                'invoice_footer',

            ]);
        });
    }
};