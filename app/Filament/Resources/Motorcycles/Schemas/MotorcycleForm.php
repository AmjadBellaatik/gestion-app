<?php

namespace App\Filament\Resources\Motorcycles\Schemas;

use App\Models\Client;
use App\Models\Product;
use App\Models\Reseller;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

use Filament\Schemas\Schema;

class MotorcycleForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                Select::make('product_id')
                    ->label(
                        __('messages.product')
                    )
                    ->relationship(
                        'product',
                        'name'
                    )
                    ->searchable(),

                TextInput::make('brand')
                    ->label(
                        __('messages.brand')
                    )
                    ->required(),

                TextInput::make('model')
                    ->label(
                        __('messages.model')
                    )
                    ->required(),

                TextInput::make('year')
                    ->label(
                        __('messages.year')
                    )
                    ->numeric(),

                TextInput::make('color')
                    ->label(
                        __('messages.color')
                    ),

                TextInput::make('vin_number')
                    ->label(
                        __('messages.vin_number')
                    )
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('engine_number')
                    ->label(
                        __('messages.engine_number')
                    ),

                Select::make('status')
                    ->label(
                        __('messages.status')
                    )
                    ->options([

                        'available' =>
                            __('messages.available'),

                        'reserved' =>
                            __('messages.reserved'),

                        'sold' =>
                            __('messages.sold'),

                        'in_repair' =>
                            __('messages.in_repair'),

                    ])
                    ->default('available')
                    ->required(),

                Select::make('client_id')
                    ->label(
                        __('messages.client')
                    )
                    ->relationship(
                        'client',
                        'full_name'
                    )
                    ->searchable(),

                Select::make('reseller_id')
                    ->label(
                        __('messages.reseller')
                    )
                    ->relationship(
                        'reseller',
                        'name'
                    )
                    ->searchable(),

            ]);
    }
}