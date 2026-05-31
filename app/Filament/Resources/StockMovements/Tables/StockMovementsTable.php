<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class StockMovementsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('reference')
                    ->label(__('messages.reference'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock_item')
                    ->label(__('messages.product'))
                    ->state(fn ($record) => $record->product?->name
                        ?? trim(($record->motorcycleUnit?->motorcycleModel?->modele ?? __('messages.motorcycle_unit')) . ' - ' . ($record->motorcycleUnit?->chassis_number ?? '')))

                    ->searchable()

                    ->sortable(),

                TextColumn::make('movement_type')

                    ->label(
                        __('messages.movement_type')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'in' =>
                                __('messages.stock_in'),

                            'entry' =>
                                __('messages.stock_in'),

                            'out' =>
                                __('messages.stock_out'),

                            'exit' =>
                                __('messages.stock_out'),

                            'sale' =>
                                __('messages.sale_movement'),

                            'purchase' =>
                                __('messages.stock_entry'),

                            'transfer' =>
                                __('messages.transfer_movement'),

                            'return' =>
                                __('messages.return_movement'),

                            'adjustment' =>
                                __('messages.adjustment'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'in' => 'success',

                        'entry' => 'success',

                        'out' => 'danger',

                        'exit' => 'danger',

                        'sale' => 'danger',

                        'purchase' => 'success',

                        'transfer' => 'warning',

                        'return' => 'info',

                        'adjustment' => 'info',

                        default => 'gray',

                    }),

                TextColumn::make('quantity')

                    ->label(
                        __('messages.quantity')
                    )

                    ->numeric()

                    ->sortable(),

                TextColumn::make('warehouse.name')

                    ->label(
                        __('messages.warehouse')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('user.name')

                    ->label(
                        __('messages.user')
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

            ->defaultSort(
                'created_at',
                'desc'
            )
            ->filters([
                SelectFilter::make('product_type')
                    ->label(__('messages.type'))
                    ->options([
                        'motorcycle_unit' => __('messages.motorcycle_unit'),
                        'part' => __('messages.part'),
                        'accessory' => __('messages.accessory'),
                        'trotinette' => __('messages.trotinette'),
                        'velo_electrique' => __('messages.velo_electrique'),
                        'velo_normal' => __('messages.velo_normal'),
                        'consumable' => __('messages.consumable'),
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        if ($value === 'motorcycle_unit') {
                            return $query->whereNotNull('motorcycle_unit_id');
                        }

                        return $query->whereHas('product', fn ($productQuery) => $productQuery->where('type', $value));
                    }),
            ])

            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
