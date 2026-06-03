<?php

namespace App\Filament\Resources\MotorcycleModels\Schemas;

use App\Models\Brand;
use App\Models\Scopes\CompanyScope;
use App\Models\Supplier;
use App\Models\MotorcycleModel;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class MotorcycleModelForm
{
    public static function configure(
        Schema $schema
    ): Schema {
        return $schema
            ->components([
                Section::make(__('messages.homologation'))
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                            ->label(__('messages.brand'))
                            ->options(fn () => Brand::withoutGlobalScope(CompanyScope::class)
                                ->with('company')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Brand $brand) => [
                                    $brand->id => trim($brand->name . ' - ' . ($brand->company?->name ?? '')),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $brand = $state ? Brand::withoutGlobalScope(CompanyScope::class)->find($state) : null;
                                $set('marque', $brand?->name);
                            })
                            ->createOptionForm([
                                TextInput::make('name')->label(__('messages.name'))->required()->maxLength(255),
                                TextInput::make('accreditation_reference')->label(__('messages.accreditation_reference'))->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data) => Brand::create($data)->id),
                        Forms\Components\TextInput::make('titre_homologation')
                            ->label(__('messages.homologation_title')),
                        Forms\Components\DatePicker::make('date_homologation')
                            ->label(__('messages.homologation_date')),
                        Forms\Components\TextInput::make('price_ttc')
                            ->label(__('messages.price_ttc'))
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('reseller_price')
                            ->label(__('messages.reseller_price'))
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('stock_alert')
                            ->label(__('messages.stock_alert'))
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Section::make(__('messages.vehicle_identification'))
                    ->schema([
                        Forms\Components\TextInput::make('marque')
                            ->label(__('messages.brand'))
                            ->hidden(fn ($get) => filled($get('brand_id')))
                            ->dehydrated()
                            ->required(fn ($get) => !filled($get('brand_id'))),
                        Forms\Components\TextInput::make('genre')
                            ->label(__('messages.genre')),
                        Forms\Components\TextInput::make('type')
                            ->label(__('messages.type')),
                        Forms\Components\TextInput::make('variante')
                            ->label(__('messages.variante'))
                            ->rule(
                                fn (Get $get, ?MotorcycleModel $record) => Rule::unique('motorcycle_models', 'variante')
                                    ->where('type', $get('type'))
                                    ->ignore($record?->getKey())
                            )
                            ->validationMessages([
                                'unique' => __('messages.type_variante_already_exists'),
                            ]),
                        Forms\Components\TextInput::make('version')
                            ->label(__('messages.version')),
                        Forms\Components\TextInput::make('modele')
                            ->label(__('messages.model'))
                            ->required(),
                        Forms\Components\TextInput::make('categorie')
                            ->label(__('messages.category')),
                        Forms\Components\Select::make('usine_fabrication')
                            ->label(__('messages.manufacturing_plant'))
                            ->options(fn () => Supplier::query()
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->toArray())
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('digit_uf')
                            ->label(__('messages.uf_digit')),
                        Forms\Components\TextInput::make('presente_par')
                            ->label(__('messages.presented_by')),
                        Forms\Components\TextInput::make('pays_origine')
                            ->label(__('messages.country_origin')),
                        Forms\Components\Textarea::make('objet')
                            ->label(__('messages.object'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('messages.engine'))
                    ->schema([
                        Forms\Components\TextInput::make('alesage')
                            ->label(__('messages.alesage_mm')),
                        Forms\Components\TextInput::make('course')
                            ->label(__('messages.course_mm')),
                        Forms\Components\TextInput::make('nombre_cylindres')
                            ->label(__('messages.cylinders'))
                            ->numeric(),
                        Forms\Components\TextInput::make('cylindree')
                            ->label(__('messages.engine_capacity_cm3')),
                        Forms\Components\TextInput::make('carburant')
                            ->label(__('messages.fuel')),
                        Forms\Components\TextInput::make('puissance_fiscale')
                            ->label(__('messages.fiscal_power_cv')),
                        Forms\Components\TextInput::make('puissance_effective')
                            ->label(__('messages.effective_power_kw')),
                        Forms\Components\TextInput::make('niveau_dep')
                            ->label(__('messages.pollution_level')),
                    ])
                    ->columns(2),

                Section::make(__('messages.weight_kg'))
                    ->schema([
                        Forms\Components\TextInput::make('pav_avant')->label(__('messages.pav_front')),
                        Forms\Components\TextInput::make('pav_arriere')->label(__('messages.pav_rear')),
                        Forms\Components\TextInput::make('poids_vide_total')->label(__('messages.empty_weight')),
                        Forms\Components\TextInput::make('ptc_avant')->label(__('messages.ptc_front')),
                        Forms\Components\TextInput::make('ptc_arriere')->label(__('messages.ptc_rear')),
                        Forms\Components\TextInput::make('ptac')->label(__('messages.ptac')),
                        Forms\Components\TextInput::make('ptra')->label(__('messages.ptra')),
                        Forms\Components\TextInput::make('ptmcr')->label(__('messages.ptmcr')),
                    ])
                    ->columns(2),

                Section::make(__('messages.dimensions_mm'))
                    ->schema([
                        Forms\Components\TextInput::make('longueur_hors_tout')->label(__('messages.overall_length')),
                        Forms\Components\TextInput::make('largeur_hors_tout')->label(__('messages.overall_width')),
                        Forms\Components\TextInput::make('porte_a_faux_arriere')->label(__('messages.rear_overhang')),
                        Forms\Components\TextInput::make('porte_a_faux_avant')->label(__('messages.front_overhang')),
                        Forms\Components\TextInput::make('empattement_1_2')->label(__('messages.wheelbase_1_2')),
                        Forms\Components\TextInput::make('empattement_2_3')->label(__('messages.wheelbase_2_3')),
                        Forms\Components\TextInput::make('empattement_3_4')->label(__('messages.wheelbase_3_4')),
                    ])
                    ->columns(2),

                Section::make(__('messages.additional_characteristics'))
                    ->schema([
                        Forms\Components\TextInput::make('pneu_avant')->label(__('messages.front_tyre')),
                        Forms\Components\TextInput::make('pneu_arriere')->label(__('messages.rear_tyre')),
                        Forms\Components\TextInput::make('boite_vitesse')->label(__('messages.gearbox')),
                        Forms\Components\TextInput::make('vitesse_max')->label(__('messages.max_speed_kmh')),
                        Forms\Components\TextInput::make('carrossage_int')->label(__('messages.bodywork_int')),
                        Forms\Components\TextInput::make('carrossage_ext')->label(__('messages.bodywork_ext')),
                        Forms\Components\TextInput::make('nombre_places')
                            ->label(__('messages.seats'))
                            ->numeric(),
                        Forms\Components\TextInput::make('volume')->label(__('messages.volume')),
                        Forms\Components\TextInput::make('utilisation_vehicule')
                            ->label(__('messages.vehicle_usage')),
                    ])
                    ->columns(2),
            ]);
    }
}
