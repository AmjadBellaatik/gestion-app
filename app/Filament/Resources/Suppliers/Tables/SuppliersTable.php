<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SuppliersTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('name')

                    ->label(
                        __('messages.supplier')
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

                TextColumn::make('balance')

                    ->label(
                        __('messages.balance')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('total_purchases')

                    ->label(
                        __('messages.total_purchases')
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

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()
                    ->requiresConfirmation(),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->requiresConfirmation(),

                ]),

            ]);
    }
}