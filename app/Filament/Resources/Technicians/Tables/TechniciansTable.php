<?php

namespace App\Filament\Resources\Technicians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Table;

class TechniciansTable
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

                TextColumn::make('phone')

                    ->label(
                        __('messages.phone')
                    )

                    ->searchable(),

                TextColumn::make('speciality')

                    ->label(
                        __('messages.speciality')
                    )

                    ->searchable()

                    ->badge(),

                IconColumn::make('is_active')

                    ->label(
                        __('messages.is_active')
                    )

                    ->boolean(),

                TextColumn::make('created_at')

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable()

                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('updated_at')

                    ->label(
                        __('messages.updated_at')
                    )

                    ->dateTime()

                    ->sortable()

                    ->toggleable(
                        isToggledHiddenByDefault: true
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