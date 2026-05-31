<?php

namespace App\Filament\Resources\Funds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

use Filament\Tables\Table;

class FundsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make(
                    'name'
                )

                    ->label(
                        __('messages.name')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'cash' =>
                                __('messages.cash'),

                            'bank' =>
                                __('messages.bank'),

                            'mobile' =>
                                __('messages.mobile'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'cash' => 'success',

                        'bank' => 'info',

                        'mobile' => 'warning',

                        default => 'gray',

                    })

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'balance'
                )

                    ->label(
                        __('messages.balance')
                    )

                    ->money('MAD')

                    ->sortable(),

                IconColumn::make(
                    'is_active'
                )

                    ->label(
                        __('messages.is_active')
                    )

                    ->boolean(),

                TextColumn::make(
                    'created_at'
                )

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable()

                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make(
                    'updated_at'
                )

                    ->label(
                        __('messages.updated_at')
                    )

                    ->dateTime()

                    ->sortable()

                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

            ])

            ->filters([

                SelectFilter::make(
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->options([

                        'cash' =>
                            __('messages.cash'),

                        'bank' =>
                            __('messages.bank'),

                        'mobile' =>
                            __('messages.mobile'),

                    ]),

                TernaryFilter::make(
                    'is_active'
                )

                    ->label(
                        __('messages.is_active')
                    ),

            ])

            ->actions([

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                ]),

            ]);
    }
}