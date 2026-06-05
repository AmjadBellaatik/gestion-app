<?php

namespace App\Filament\Resources\MotorcycleModels\Tables;


use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

use Filament\Tables\Columns\TextColumn;

class MotorcycleModelsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('marque')

                    ->label(
                        __('messages.brand')
                    )

                    ->getStateUsing(fn ($record) => $record->marque ?: $record->brand?->name)

                    ->searchable()

                    ->sortable()

                    ->placeholder('—'),

                TextColumn::make('modele')

                    ->label(
                        __('messages.model')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('type')

                    ->label(
                        __('messages.type')
                    )

                    ->searchable()

                    ->sortable()

                    ->placeholder('—'),

                TextColumn::make('variante')

                    ->label(
                        __('messages.variante')
                    )

                    ->searchable()

                    ->sortable()

                    ->placeholder('—'),

                TextColumn::make('categorie')

                    ->label(
                        __('messages.category')
                    )

                    ->badge()

                    ->sortable(),

                TextColumn::make('carburant')

                    ->label(
                        __('messages.fuel')
                    )

                    ->badge()

                    ->sortable(),

                TextColumn::make('date_homologation')

                    ->label(
                        __('messages.homologation_date')
                    )

                    ->date()

                    ->sortable(),

                TextColumn::make('price_ttc')
                    ->label(__('messages.price_ttc'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('reseller_price')
                    ->label(__('messages.reseller_price'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('stock_alert')
                    ->label(__('messages.stock_alert'))
                    ->numeric()
                    ->sortable(),

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
                DeleteAction::make(),
            ]);
    }
}
