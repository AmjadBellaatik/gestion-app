<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (
            Blueprint $table
        ) {

            if (! Schema::hasColumn('motorcycles', 'category')) {

                $table->string('category')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'homologation_number')) {

                $table->string('homologation_number')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'homologation_date')) {

                $table->date('homologation_date')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'cylinder')) {

                $table->string('cylinder')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'fuel_type')) {

                $table->string('fuel_type')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'power')) {

                $table->string('power')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'seating_capacity')) {

                $table->integer('seating_capacity')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'fiscal_power')) {

                $table->string('fiscal_power')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'cooling_system')) {

                $table->string('cooling_system')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'transmission')) {

                $table->string('transmission')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'braking_system')) {

                $table->string('braking_system')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'front_tire')) {

                $table->string('front_tire')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'rear_tire')) {

                $table->string('rear_tire')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'weight')) {

                $table->decimal(
                    'weight',
                    10,
                    2
                )->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'importer_reference')) {

                $table->string('importer_reference')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'customs_reference')) {

                $table->string('customs_reference')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'conformity_reference')) {

                $table->string('conformity_reference')
                    ->nullable();

            }

            if (! Schema::hasColumn('motorcycles', 'registration_status')) {

                $table->string('registration_status')
                    ->nullable();

            }

        });
    }

    public function down(): void
    {
        //
    }
};