<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table) {
            if (! Schema::hasColumn('motorcycle_models', 'longueur_hors_tout')) {
                $table->string('longueur_hors_tout')->nullable()->after('ptmcr');
            }

            if (! Schema::hasColumn('motorcycle_models', 'largeur_hors_tout')) {
                $table->string('largeur_hors_tout')->nullable()->after('longueur_hors_tout');
            }

            if (! Schema::hasColumn('motorcycle_models', 'porte_a_faux_arriere')) {
                $table->string('porte_a_faux_arriere')->nullable()->after('largeur_hors_tout');
            }

            if (! Schema::hasColumn('motorcycle_models', 'porte_a_faux_avant')) {
                $table->string('porte_a_faux_avant')->nullable()->after('porte_a_faux_arriere');
            }

            if (! Schema::hasColumn('motorcycle_models', 'empattement_1_2')) {
                $table->string('empattement_1_2')->nullable()->after('porte_a_faux_avant');
            }

            if (! Schema::hasColumn('motorcycle_models', 'empattement_2_3')) {
                $table->string('empattement_2_3')->nullable()->after('empattement_1_2');
            }

            if (! Schema::hasColumn('motorcycle_models', 'empattement_3_4')) {
                $table->string('empattement_3_4')->nullable()->after('empattement_2_3');
            }

            if (! Schema::hasColumn('motorcycle_models', 'volume')) {
                $table->string('volume')->nullable()->after('nombre_places');
            }
        });
    }

    public function down(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table) {
            $table->dropColumn([
                'longueur_hors_tout',
                'largeur_hors_tout',
                'porte_a_faux_arriere',
                'porte_a_faux_avant',
                'empattement_1_2',
                'empattement_2_3',
                'empattement_3_4',
                'volume',
            ]);
        });
    }
};
