<?php

namespace App\Filament\Resources\MotorcycleUnits\Schemas;

use App\Models\Client;
use App\Models\MotorcycleModel;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Schemas\Schema;

class MotorcycleUnitForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                Forms\Components\Select::make(
                    'motorcycle_model_id'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->options(function () {
                        return MotorcycleModel::orderBy('type')
                            ->orderBy('variante')
                            ->get()
                            ->mapWithKeys(fn ($m) => [
                                $m->id => trim($m->type . ($m->variante ? ' - ' . $m->variante : '')),
                            ]);
                    })

                    ->searchable()

                    ->preload()

                    ->required()

                    ->live()

                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            return;
                        }
                        $model = MotorcycleModel::find($state);
                        if ($model?->boite_vitesse) {
                            $set('boite_vitesse', $model->boite_vitesse);
                        }
                    }),

                Forms\Components\Select::make(
                    'warehouse_id'
                )

                    ->label(
                        __('messages.warehouse')
                    )

                    ->relationship(
                        'warehouse',
                        'name'
                    )

                    ->options(function () {

                        $user = auth()->user();

                        if (

                            $user->hasRole(
                                'Super Admin'
                            )

                            ||

                            $user->hasRole(
                                'Admin'
                            )

                        ) {

                            return Warehouse::active()->orderBy('name')->pluck(
                                'name',
                                'id'
                            );
                        }

                        return $user->warehouses()
                            ->where('is_active', true)

                            ->pluck(
                                'name',
                                'warehouses.id'
                            );
                    })

                    ->searchable()

                    ->preload()

                    ->required(),

                Forms\Components\TextInput::make(
                    'chassis_number'
                )

                    ->label(
                        __('messages.chassis_number')
                    )

                    ->required()

                    ->unique(
                        ignoreRecord: true
                    ),

                Forms\Components\TextInput::make(
                    'engine_number'
                )

                    ->label(
                        __('messages.engine_number')
                    )

                    ->required(),

                Forms\Components\TextInput::make(
                    'color'
                )

                    ->label(
                        __('messages.color')
                    ),

                Forms\Components\TextInput::make(
                    'boite_vitesse'
                )

                    ->label(
                        __('messages.boite_vitesse')
                    ),

                Forms\Components\Select::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->options([

                        'in_stock' =>

                            __('messages.in_stock'),

                        'reserved' =>

                            __('messages.reserved'),

                        'sold' =>

                            __('messages.sold'),

                        'repair' =>

                            __('messages.repair'),

                    ])

                    ->default('in_stock')

                    ->required(),

                Forms\Components\DatePicker::make(
                    'purchase_date'
                )

                    ->label(
                        __('messages.purchase_date')
                    )
                    ->visible(fn ($record) => (bool) $record)
                    ->disabled(),

                Forms\Components\DatePicker::make(
                    'sale_date'
                )

                    ->label(
                        __('messages.sale_date')
                    )
                    ->visible(fn ($record, $get) => (bool) $record && $get('status') === 'sold')
                    ->disabled(),

                Forms\Components\Select::make(
                    'client_id'
                )

                    ->label(
                        __('messages.client')
                    )

                    ->options(

                        Client::query()

                            ->get()

                            ->pluck(
                                'display_name',
                                'id'
                            )

                            ->toArray()

                    )

                    ->searchable()

                    ->preload()
                    ->visible(fn ($record, $get) => (bool) $record && $get('status') === 'sold')
                    ->disabled(),

            ]);
    }
}
