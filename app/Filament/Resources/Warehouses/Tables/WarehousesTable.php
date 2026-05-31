<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class WarehousesTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('name')

                    ->label(
                        __('messages.name')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('code')

                    ->label(
                        __('messages.code')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('phone')

                    ->label(
                        __('messages.phone')
                    )

                    ->searchable(),

                IconColumn::make('is_active')

                    ->label(
                        __('messages.is_active')
                    )

                    ->boolean(),

                TextColumn::make('users.name')

                    ->label(
                        __('messages.users')
                    )

                    ->badge(),

                TextColumn::make('created_at')

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable(),

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()

                    ->requiresConfirmation(),

            ]);
    }
}
