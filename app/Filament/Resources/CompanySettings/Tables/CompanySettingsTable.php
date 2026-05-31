<?php

namespace App\Filament\Resources\CompanySettings\Tables;

use Filament\Tables\Table;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;


class CompanySettingsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                ImageColumn::make(
                    'logo'
                )

                    ->label(
                        __('messages.logo')
                    )

                    ->getStateUsing(
                        fn ($record): ?string => $record->logo
                            ? asset('storage/' . $record->logo)
                            : null
                    )

                    ->square(),

                TextColumn::make(
                    'name'
                )

                    ->label(
                        __('messages.company')
                    )

                    ->searchable(),

                TextColumn::make(
                    'ice'
                )

                    ->label(
                        __('messages.ice')
                    ),

                TextColumn::make(
                    'phone'
                )

                    ->label(
                        __('messages.phone')
                    ),

                TextColumn::make(
                    'default_language'
                )

                    ->label(
                        __('messages.default_language')
                    ),

            ])

            ->actions([

            ])

            ->emptyStateHeading(
                __('messages.no_company_settings')
            );
    }
}
