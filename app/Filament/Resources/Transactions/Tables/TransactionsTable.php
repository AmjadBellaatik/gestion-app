<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\SelectFilter;

use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make(
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->badge()

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'category'
                )

                    ->label(
                        __('messages.category')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'amount'
                )

                    ->label(
                        __('messages.amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make(
                    'direction'
                )

                    ->label(
                        __('messages.direction')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'in' =>
                                __('messages.in'),

                            'out' =>
                                __('messages.out'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'in' => 'success',

                        'out' => 'danger',

                        default => 'gray',

                    }),

                TextColumn::make(
                    'reference_type'
                )

                    ->label(
                        __('messages.reference_type')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'reference_id'
                )

                    ->label(
                        __('messages.reference')
                    )

                    ->sortable(),

                TextColumn::make(
                    'payment_method'
                )

                    ->label(
                        __('messages.payment_method')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'cash' =>
                                __('messages.cash'),

                            'card' =>
                                __('messages.card'),

                            'bank_transfer' =>
                                __('messages.bank_transfer'),

                            'check' =>
                                __('messages.check'),

                            default => $state,

                        }
                    ),

                TextColumn::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'pending' =>
                                __('messages.pending'),

                            'validated' =>
                                __('messages.validated'),

                            'cancelled' =>
                                __('messages.cancelled'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'pending' => 'warning',

                        'validated' => 'success',

                        'cancelled' => 'danger',

                        default => 'gray',

                    }),

                TextColumn::make(
                    'creator.name'
                )

                    ->label(
                        __('messages.user')
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
                    'direction'
                )

                    ->label(
                        __('messages.direction')
                    )

                    ->options([

                        'in' =>
                            __('messages.in'),

                        'out' =>
                            __('messages.out'),

                    ]),

                SelectFilter::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->options([

                        'pending' =>
                            __('messages.pending'),

                        'validated' =>
                            __('messages.validated'),

                        'cancelled' =>
                            __('messages.cancelled'),

                    ]),

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