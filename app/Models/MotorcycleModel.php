<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MotorcycleModel extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [
        'titre_homologation',
        'date_homologation',
        'price_ttc',
        'reseller_price',
        'stock_alert',
        'brand_id',
        'marque',
        'genre',
        'type',
        'variante',
        'version',
        'modele',
        'categorie',
        'usine_fabrication',
        'digit_uf',
        'presente_par',
        'pays_origine',
        'objet',
        'alesage',
        'course',
        'nombre_cylindres',
        'cylindree',
        'carburant',
        'puissance_fiscale',
        'puissance_effective',
        'niveau_dep',
        'pav_avant',
        'pav_arriere',
        'poids_vide_total',
        'ptc_avant',
        'ptc_arriere',
        'ptac',
        'ptra',
        'ptmcr',
        'longueur_hors_tout',
        'largeur_hors_tout',
        'porte_a_faux_arriere',
        'porte_a_faux_avant',
        'empattement_1_2',
        'empattement_2_3',
        'empattement_3_4',
        'pneu_avant',
        'pneu_arriere',
        'boite_vitesse',
        'vitesse_max',
        'carrossage_int',
        'carrossage_ext',
        'nombre_places',
        'volume',
        'utilisation_vehicule',
    ];

    protected $casts = [
        'date_homologation' => 'date',
        'price_ttc' => 'decimal:2',
        'reseller_price' => 'decimal:2',
        'stock_alert' => 'integer',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(MotorcycleUnit::class);
    }

    public function homologation(): HasOne
    {
        return $this->hasOne(MotorcycleHomologation::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
