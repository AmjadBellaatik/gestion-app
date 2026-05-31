<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\MotorcycleUnit;

use Filament\Forms;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class StockTransferForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                Section::make(

                    __('messages.transfer_information')

                )

                    ->schema([

                        Forms\Components\Select::make(
                            'from_warehouse_id'
                        )

                            ->label(
                                __('messages.from_warehouse')
                            )

                            ->relationship(
                                'fromWarehouse',
                                'name',
                                fn ($query) => $query->where('is_active', true)
                            )

                            ->required()

                            ->searchable()

                            ->preload(),

                        Forms\Components\Select::make(
                            'to_warehouse_id'
                        )

                            ->label(
                                __('messages.to_warehouse')
                            )

                            ->relationship(
                                'toWarehouse',
                                'name',
                                fn ($query) => $query->where('is_active', true)
                            )

                            ->required()

                            ->searchable()

                            ->preload(),

                        Forms\Components\Textarea::make(
                            'notes'
                        )

                            ->label(
                                __('messages.notes')
                            ),

                    ])

                    ->columns(2),

                Section::make(

                    __('messages.transfer_items')

                )

                    ->schema([

                        Forms\Components\Repeater::make(
                            'items'
                        )

                            ->relationship()

                            ->schema([

                                Forms\Components\Select::make(
                                    'product_id'
                                )

                                    ->label(
                                        __('messages.product')
                                    )

                                    ->relationship(
                                        'product',
                                        'name'
                                    )

                                    ->searchable()

                                    ->preload(),

                                Forms\Components\Select::make(
                                    'motorcycle_unit_id'
                                )

                                    ->label(
                                        __('messages.motorcycle_unit')
                                    )

                                    ->relationship(
                                        'motorcycleUnit',
                                        'chassis_number'
                                    )

                                    ->searchable()

                                    ->preload(),

                                Forms\Components\TextInput::make(
                                    'quantity'
                                )

                                    ->label(
                                        __('messages.quantity')
                                    )

                                    ->numeric()

                                    ->default(1)

                                    ->required(),

                            ])

                            ->columns(3)

                            ->defaultItems(1),

                    ]),

            ]);
    }
}