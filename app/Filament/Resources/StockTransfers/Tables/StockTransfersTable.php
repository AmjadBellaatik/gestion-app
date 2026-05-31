<?php

namespace App\Filament\Resources\StockTransfers\Tables;

use App\Services\Stock\StockTransferService;

use Filament\Actions\Action;

use Filament\Tables;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class StockTransfersTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('reference')

                    ->label(
                        __('messages.reference')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'fromWarehouse.name'
                )

                    ->label(
                        __('messages.from_warehouse')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'toWarehouse.name'
                )

                    ->label(
                        __('messages.to_warehouse')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('status')

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
                        __('messages.created_by')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('created_at')

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

                        'pending' =>

                            __('messages.pending'),

                        'validated' =>

                            __('messages.validated'),

                        'cancelled' =>

                            __('messages.cancelled'),

                    ]),

            ])

            ->actions([

                Action::make(
                    'validate_transfer'
                )

                    ->label(
                        __('messages.validate')
                    )

                    ->icon(
                        'heroicon-o-check'
                    )

                    ->color('success')

                    ->visible(fn ($record) =>

                        $record->status ===
                        'pending'
                    )

                    ->requiresConfirmation()

                    ->action(fn ($record) =>

                        StockTransferService::validate(
                            $record
                        )
                    ),

            ]);
    }
}