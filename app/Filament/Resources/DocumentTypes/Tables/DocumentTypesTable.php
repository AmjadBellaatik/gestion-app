<?php

namespace App\Filament\Resources\DocumentTypes\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class DocumentTypesTable
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

                TextColumn::make('prefix')

                    ->label(
                        __('messages.prefix')
                    )

                    ->searchable()

                    ->sortable(),

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