<?php

namespace App\Filament\Resources\MotorcycleModels\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MotorcycleModelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.homologation'))
                    ->schema([
                        TextEntry::make('titre_homologation')
                            ->label(__('messages.homologation_title')),
                        TextEntry::make('date_homologation')
                            ->label(__('messages.homologation_date'))
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('brand.name')
                            ->label(__('messages.brand'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make(__('messages.vehicle_identification'))
                    ->schema([
                        self::entry('marque', 'marque'),
                        self::entry('genre', 'genre'),
                        self::entry('type', 'type'),
                        self::entry('variante', 'variante'),
                        self::entry('version', 'version'),
                        self::entry('categorie', 'category'),
                        self::entry('usine_fabrication', 'manufacturing_plant'),
                        self::entry('digit_uf', 'uf_digit'),
                        self::entry('presente_par', 'presented_by'),
                        self::entry('pays_origine', 'country_origin'),
                        self::entry('objet', 'object')->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('messages.engine'))
                    ->schema([
                        self::entry('alesage', 'alesage_mm'),
                        self::entry('course', 'course_mm'),
                        self::entry('nombre_cylindres', 'cylinders'),
                        self::entry('cylindree', 'engine_capacity_cm3'),
                        self::entry('carburant', 'fuel'),
                        self::entry('puissance_fiscale', 'fiscal_power_cv'),
                        self::entry('puissance_effective', 'effective_power_kw'),
                        self::entry('niveau_dep', 'pollution_level'),
                    ])
                    ->columns(2),

                Section::make(__('messages.weight_kg'))
                    ->schema([
                        self::entry('pav_avant', 'pav_front'),
                        self::entry('pav_arriere', 'pav_rear'),
                        self::entry('poids_vide_total', 'empty_weight'),
                        self::entry('ptc_avant', 'ptc_front'),
                        self::entry('ptc_arriere', 'ptc_rear'),
                        self::entry('ptac', 'ptac'),
                        self::entry('ptra', 'ptra'),
                        self::entry('ptmcr', 'ptmcr'),
                    ])
                    ->columns(2),

                Section::make(__('messages.dimensions_mm'))
                    ->schema([
                        self::entry('longueur_hors_tout', 'overall_length'),
                        self::entry('largeur_hors_tout', 'overall_width'),
                        self::entry('porte_a_faux_arriere', 'rear_overhang'),
                        self::entry('porte_a_faux_avant', 'front_overhang'),
                        self::entry('empattement_1_2', 'wheelbase_1_2'),
                        self::entry('empattement_2_3', 'wheelbase_2_3'),
                        self::entry('empattement_3_4', 'wheelbase_3_4'),
                    ])
                    ->columns(2),

                Section::make(__('messages.additional_characteristics'))
                    ->schema([
                        self::entry('pneu_avant', 'front_tyre'),
                        self::entry('pneu_arriere', 'rear_tyre'),
                        self::entry('boite_vitesse', 'gearbox'),
                        self::entry('vitesse_max', 'max_speed_kmh'),
                        self::entry('carrossage_int', 'bodywork_int'),
                        self::entry('carrossage_ext', 'bodywork_ext'),
                        self::entry('nombre_places', 'seats'),
                        self::entry('volume', 'volume'),
                        self::entry('utilisation_vehicule', 'vehicle_usage'),
                    ])
                    ->columns(2),
            ]);
    }

    private static function entry(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label(__('messages.' . $label))
            ->placeholder('-');
    }
}
