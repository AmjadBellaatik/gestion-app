<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table) {
            if (! Schema::hasColumn('motorcycle_models', 'price_ttc')) {
                $table->decimal('price_ttc', 12, 2)
                    ->default(0)
                    ->after('date_homologation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table) {
            if (Schema::hasColumn('motorcycle_models', 'price_ttc')) {
                $table->dropColumn('price_ttc');
            }
        });
    }
};

