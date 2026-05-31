<?php

namespace App\Filament\Resources\Motorcycles\Tables;

use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class MotorcyclesTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('brand')
                    ->label(
                        __('messages.brand')
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model')
                    ->label(
                        __('messages.model')
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('year')
                    ->label(
                        __('messages.year')
                    )
                    ->sortable(),

                TextColumn::make('color')
                    ->label(
                        __('messages.color')
                    ),

                TextColumn::make('vin_number')
                    ->label(
                        __('messages.vin_number')
                    )
                    ->searchable(),

                TextColumn::make('engine_number')
                    ->label(
                        __('messages.engine_number')
                    )
                    ->searchable(),

                TextColumn::make('status')
                    ->label(
                        __('messages.status')
                    )
                    ->badge(),

                TextColumn::make('client.full_name')
                    ->label(
                        __('messages.client')
                    ),

                TextColumn::make('reseller.name')
                    ->label(
                        __('messages.reseller')
                    ),

                TextColumn::make('created_at')
                    ->dateTime(),

            ])

            ->defaultSort(
                'created_at',
                'desc'
            );
    }
}