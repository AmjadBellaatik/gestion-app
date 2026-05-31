<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {

            if (! Schema::hasColumn('clients', 'client_type')) {

                $table->string('client_type')
                    ->default('person')
                    ->after('reseller_id');
            }

            if (! Schema::hasColumn('clients', 'first_name')) {

                $table->string('first_name')
                    ->nullable()
                    ->after('client_type');
            }

            if (! Schema::hasColumn('clients', 'last_name')) {

                $table->string('last_name')
                    ->nullable()
                    ->after('first_name');
            }

            if (! Schema::hasColumn('clients', 'company_name')) {

                $table->string('company_name')
                    ->nullable()
                    ->after('last_name');
            }

            if (! Schema::hasColumn('clients', 'administration_name')) {

                $table->string('administration_name')
                    ->nullable()
                    ->after('company_name');
            }

            if (! Schema::hasColumn('clients', 'cin')) {

                $table->string('cin')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'birth_date')) {

                $table->date('birth_date')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'nationality')) {

                $table->string('nationality')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'ice')) {

                $table->string('ice')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'rc')) {

                $table->string('rc')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'if')) {

                $table->string('if')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'patente')) {

                $table->string('patente')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'representative_name')) {

                $table->string('representative_name')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'department')) {

                $table->string('department')
                    ->nullable();
            }

            if (! Schema::hasColumn('clients', 'responsible_person')) {

                $table->string('responsible_person')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {

            $columns = [

                'client_type',
                'first_name',
                'last_name',
                'company_name',
                'administration_name',
                'cin',
                'birth_date',
                'nationality',
                'ice',
                'rc',
                'if',
                'patente',
                'representative_name',
                'department',
                'responsible_person',

            ];

            foreach ($columns as $column) {

                if (Schema::hasColumn('clients', $column)) {

                    $table->dropColumn($column);
                }
            }
        });
    }
};