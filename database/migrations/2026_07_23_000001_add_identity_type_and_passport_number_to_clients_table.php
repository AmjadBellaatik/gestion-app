<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Which identity document the client registered with. Existing rows
            // (all pre-dating this column) default to 'cin' since cin is the only
            // identity field that has ever existed — preserves current data as-is.
            $table->enum('identity_type', ['cin', 'passport'])
                ->default('cin')
                ->after('cin');

            $table->string('passport_number')
                ->nullable()
                ->after('identity_type');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['identity_type', 'passport_number']);
        });
    }
};
