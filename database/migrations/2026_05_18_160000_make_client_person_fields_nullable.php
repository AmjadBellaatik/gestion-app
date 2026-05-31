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

            foreach (['first_name', 'last_name', 'cin', 'nationality'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->string($column)->nullable()->change();
                }
            }

            if (Schema::hasColumn('clients', 'birth_date')) {
                $table->date('birth_date')->nullable()->change();
            }

        });
    }

    public function down(): void
    {
        Schema::table('clients', function (
            Blueprint $table
        ) {

            foreach (['first_name', 'last_name', 'cin', 'nationality'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->string($column)->nullable(false)->change();
                }
            }

            if (Schema::hasColumn('clients', 'birth_date')) {
                $table->date('birth_date')->nullable(false)->change();
            }

        });
    }
};
