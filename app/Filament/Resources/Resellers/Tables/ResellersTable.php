<?php

namespace App\Filament\Resources\Resellers\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ResellersTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('name')

                    ->label(
                        __('messages.reseller')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('phone')

                    ->label(
                        __('messages.phone')
                    )

                    ->searchable(),

                TextColumn::make('email')

                    ->label(
                        __('messages.email')
                    )

                    ->searchable(),

                TextColumn::make('credit_balance')

                    ->label(
                        __('messages.credit_balance')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('total_orders')

                    ->label(
                        __('messages.total_orders')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('total_paid')

                    ->label(
                        __('messages.total_paid')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('created_at')

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable(),

            ])

            ->defaultSort(
                'created_at',
                'desc'
            )

            ->actions([

            ]);
    }
}