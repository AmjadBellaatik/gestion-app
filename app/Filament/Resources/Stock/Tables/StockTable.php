<?php

namespace App\Filament\Resources\Stock\Tables;

use App\Filament\Resources\Stock\Support\StockActions;
use App\Models\StockItem;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.product'))
                    ->searchable()
                    ->sortable()
                    ->color(fn (StockItem $record): string => $record->is_low_stock ? 'danger' : 'gray')
                    ->weight(fn (StockItem $record): string => $record->is_low_stock ? 'bold' : 'medium'),

                TextColumn::make('reference')
                    ->label(__('messages.reference'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('item_kind')
                    ->label(__('messages.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'motorcycle_model' ? __('messages.motorcycle_model') : __('messages.product')),

                TextColumn::make('type')
                    ->label(__('messages.category'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state && __('messages.'.$state) !== 'messages.'.$state ? __('messages.'.$state) : ($state ?: '-')),

                TextColumn::make('stock_alert')
                    ->label(__('messages.stock_alert'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('live_quantity')
                    ->label(__('messages.live_quantity'))
                    ->state(fn (StockItem $record): float => $record->live_quantity)
                    ->color(fn (StockItem $record): string => $record->is_low_stock ? 'danger' : 'gray')
                    ->weight(fn (StockItem $record): string => $record->is_low_stock ? 'bold' : 'medium'),
            ])
            ->filters([
                SelectFilter::make('item_kind')
                    ->label(__('messages.type'))
                    ->options([
                        'product' => __('messages.product'),
                        'motorcycle_model' => __('messages.motorcycle_model'),
                    ]),

                SelectFilter::make('product_type')
                    ->label(__('messages.product_type'))
                    ->options([
                        'part' => __('messages.part'),
                        'accessory' => __('messages.accessory'),
                        'trotinette' => __('messages.trotinette'),
                        'velo_electrique' => __('messages.velo_electrique'),
                        'velo_normal' => __('messages.velo_normal'),
                        'consumable' => __('messages.consumable'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('item_kind', 'product')->where('type', $data['value'])
                        : $query),

                SelectFilter::make('warehouse_id')
                    ->label(__('messages.warehouse'))
                    ->options(fn () => \App\Models\Warehouse::active()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        $warehouseId = $data['value'] ?? null;

                        if (! $warehouseId) {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($warehouseId): void {
                            $query
                                ->whereExists(function ($subQuery) use ($warehouseId): void {
                                    $subQuery
                                        ->selectRaw('1')
                                        ->from('stock_movements')
                                        ->whereColumn('stock_movements.product_id', 'stock_items.product_id')
                                        ->where('stock_movements.warehouse_id', $warehouseId);
                                })
                                ->orWhereExists(function ($subQuery) use ($warehouseId): void {
                                    $subQuery
                                        ->selectRaw('1')
                                        ->from('motorcycle_units')
                                        ->whereColumn('motorcycle_units.motorcycle_model_id', 'stock_items.motorcycle_model_id')
                                        ->where('motorcycle_units.warehouse_id', $warehouseId);
                                });
                        });
                    }),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('add_stock')
                    ->label(__('messages.add_stock'))
                    ->icon('heroicon-o-plus')
                    ->form(fn (StockItem $record): array => $record->item_kind === 'motorcycle_model'
                        ? StockActions::motorcycleUnitForm((int) $record->motorcycle_model_id)
                        : StockActions::productMovementForm((int) $record->product_id))
                    ->action(function (StockItem $record, array $data): void {
                        if ($record->item_kind === 'motorcycle_model') {
                            StockActions::addMotorcycleStock($data);

                            return;
                        }

                        StockActions::addProductStock($data);
                    }),

                Action::make('adjust_stock')
                    ->label(__('messages.adjust_stock'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (StockItem $record): bool => $record->item_kind === 'product' && StockActions::canAdjust())
                    ->form(fn (StockItem $record): array => StockActions::productAdjustmentForm((int) $record->product_id))
                    ->action(fn (StockItem $record, array $data) => StockActions::adjustProductStock($data)),
            ])
            ->defaultSort('name');
    }
}
