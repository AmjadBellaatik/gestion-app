<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'motorcycle_models',

            function (
                Blueprint $table
            ) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Homologation
                |--------------------------------------------------------------------------
                */

                $table->string('titre_homologation')->nullable();

                $table->string('marque')->nullable();

                $table->string('genre')->nullable();

                $table->string('type')->nullable();

                $table->string('variante')->nullable();

                $table->string('version')->nullable();

                $table->string('modele')->nullable();

                $table->string('categorie')->nullable();

                $table->string('usine_fabrication')->nullable();

                $table->string('digit_uf')->nullable();

                $table->string('presente_par')->nullable();

                $table->string('pays_origine')->nullable();

                $table->text('objet')->nullable();

                /*
                |--------------------------------------------------------------------------
                | Motorization
                |--------------------------------------------------------------------------
                */

                $table->string('alesage')->nullable();

                $table->string('course')->nullable();

                $table->integer('nombre_cylindres')->nullable();

                $table->integer('cylindree')->nullable();

                $table->string('carburant')->nullable();

                $table->string('puissance_fiscale')->nullable();

                $table->string('puissance_effective')->nullable();

                $table->string('niveau_dep')->nullable();

                /*
                |--------------------------------------------------------------------------
                | Weight
                |--------------------------------------------------------------------------
                */

                $table->string('pav_avant')->nullable();

                $table->string('pav_arriere')->nullable();

                $table->string('poids_vide_total')->nullable();

                $table->string('ptc_avant')->nullable();

                $table->string('ptc_arriere')->nullable();

                $table->string('ptac')->nullable();

                $table->string('ptra')->nullable();

                $table->string('ptmcr')->nullable();

                /*
                |--------------------------------------------------------------------------
                | Dimensions
                |--------------------------------------------------------------------------
                */

                $table->string('longueur_hors_tout')->nullable();

                $table->string('largeur_hors_tout')->nullable();

                $table->string('porte_a_faux_arriere')->nullable();

                $table->string('porte_a_faux_avant')->nullable();

                $table->string('empattement_1_2')->nullable();

                $table->string('empattement_2_3')->nullable();

                $table->string('empattement_3_4')->nullable();

                /*
                |--------------------------------------------------------------------------
                | Other
                |--------------------------------------------------------------------------
                */

                $table->string('pneu_avant')->nullable();

                $table->string('pneu_arriere')->nullable();

                $table->string('boite_vitesse')->nullable();

                $table->string('vitesse_max')->nullable();

                $table->string('carrossage_int')->nullable();

                $table->string('carrossage_ext')->nullable();

                $table->integer('nombre_places')->nullable();

                $table->string('volume')->nullable();

                $table->string('utilisation_vehicule')->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'motorcycle_models'
        );
    }
};
