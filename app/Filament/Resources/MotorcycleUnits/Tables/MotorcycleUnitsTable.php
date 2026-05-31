<?php

namespace App\Filament\Resources\MotorcycleUnits\Tables;


use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class MotorcycleUnitsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make(
                    'motorcycleModel.type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'chassis_number'
                )

                    ->label(
                        __('messages.chassis_number')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'in_stock' =>
                                __('messages.in_stock'),

                            'reserved' =>
                                __('messages.reserved'),

                            'sold' =>
                                __('messages.sold'),

                            'repair' =>
                                __('messages.repair'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'in_stock' => 'success',

                        'reserved' => 'warning',

                        'sold' => 'danger',

                        'repair' => 'info',

                        default => 'gray',

                    }),

                TextColumn::make(
                    'warehouse.name'
                )

                    ->label(
                        __('messages.warehouse')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'created_at'
                )

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable(),

            ])

            ->filters([

                Tables\Filters\SelectFilter::make(
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

                    ]),

            ])

            ->actions([

                EditAction::make(),

                DeleteAction::make()

                    ->requiresConfirmation(),

            ]);
    }
}
