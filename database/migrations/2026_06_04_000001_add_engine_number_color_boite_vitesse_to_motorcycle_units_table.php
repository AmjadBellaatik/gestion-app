<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycle_units', function (Blueprint $table) {
            $table->string('engine_number')->nullable()->after('chassis_number');
            $table->string('color')->nullable()->after('engine_number');
            $table->string('boite_vitesse')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('motorcycle_units', function (Blueprint $table) {
            $table->dropColumn(['engine_number', 'color', 'boite_vitesse']);
        });
    }
};
