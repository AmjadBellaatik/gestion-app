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

            if (! Schema::hasColumn('companies', 'if')) {

                $table->string('if')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'rc')) {

                $table->string('rc')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'cnss')) {

                $table->string('cnss')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'patente')) {

                $table->string('patente')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'rib')) {

                $table->string('rib')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'bank_name')) {

                $table->string('bank_name')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'legal_address')) {

                $table->text('legal_address')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'phone')) {

                $table->string('phone')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'email')) {

                $table->string('email')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'website')) {

                $table->string('website')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'city')) {

                $table->string('city')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'country')) {

                $table->string('country')
                    ->nullable();

            }

            if (! Schema::hasColumn('companies', 'tax_rate')) {

                $table->decimal(
                    'tax_rate',
                    5,
                    2
                )->default(20);

            }

            if (! Schema::hasColumn('companies', 'default_language')) {

                $table->string('default_language')
                    ->default('fr');

            }

            if (! Schema::hasColumn('companies', 'invoice_footer')) {

                $table->longText('invoice_footer')
                    ->nullable();

            }

        });
    }

    public function down(): void
    {
        //
    }
};