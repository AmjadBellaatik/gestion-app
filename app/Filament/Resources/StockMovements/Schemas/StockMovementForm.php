<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                Select::make('warehouse_id')
                    ->label(__('messages.warehouse'))
                    ->options(function (): array {
                        $user = auth()->user();
                        if ($user?->hasAnyRole(['Super Admin', 'Admin'])) {
                            return Warehouse::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray();
                        }
                        return $user?->warehouses()->where('is_active', true)->pluck('name', 'warehouses.id')->toArray() ?? [];
                    })
                    ->searchable()
                    ->required(),

                Grid::make(2)
                    ->schema([
                        Select::make('product_id')
                            ->label(__('messages.product'))
                            ->relationship('product', 'name')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                if (! $state || $get('movement_type') !== 'adjustment') {
                                    return;
                                }
                                $product = Product::withoutGlobalScopes()->find($state);
                                $currentStock = $product ? (float) $product->current_stock : 0;
                                $set('_current_stock', $currentStock);
                                $set('_new_quantity', null);
                                $set('_quantity_difference', null);
                            }),

                        Select::make('motorcycle_unit_id')
                            ->label(__('messages.motorcycle_unit'))
                            ->relationship('motorcycleUnit', 'chassis_number')
                            ->searchable(),
                    ]),

                Select::make('movement_type')
                    ->label(
                        __('messages.movement_type')
                    )
                    ->options([

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

                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                        $set('type', match ($state) {
                            'purchase', 'return' => 'entry',
                            'sale' => 'exit',
                            'transfer' => 'transfer',
                            default => 'adjustment',
                        });

                        if ($state === 'adjustment') {
                            $productId = $get('product_id');
                            if ($productId) {
                                $product = Product::withoutGlobalScopes()->find($productId);
                                $set('_current_stock', $product ? (float) $product->current_stock : 0);
                            }
                            $set('notes', __('messages.admin_adjustment'));
                        }
                    })
                    ->required(),

                Select::make('type')
                    ->label(__('messages.stock_direction'))
                    ->options([
                        'entry' => __('messages.stock_in'),
                        'exit' => __('messages.stock_out'),
                        'transfer' => __('messages.transfer'),
                        'adjustment' => __('messages.adjustment'),
                    ])
                    ->default('entry')
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                /*
                |--------------------------------------------------------------------------
                | Admin adjustment fields (visible only when movement_type = adjustment)
                |--------------------------------------------------------------------------
                */

                Grid::make(3)
                    ->schema([
                        TextInput::make('_current_stock')
                            ->label(__('messages.current_quantity'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'font-bold']),

                        TextInput::make('_new_quantity')
                            ->label(__('messages.new_quantity'))
                            ->numeric()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                                $current = (float) ($get('_current_stock') ?? 0);
                                $newQty  = (float) ($state ?? 0);
                                $diff    = $newQty - $current;

                                $set('_quantity_difference', $diff);
                                $set('quantity', abs($diff));
                                $set('type', $diff >= 0 ? 'entry' : 'exit');
                            }),

                        TextInput::make('_quantity_difference')
                            ->label(__('messages.quantity_difference'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix(fn ($get) => ($get('_quantity_difference') ?? 0) >= 0 ? '+' : ''),
                    ])
                    ->visible(fn (callable $get) => $get('movement_type') === 'adjustment'
                        && auth()->user()?->hasAnyRole(['Admin', 'Super Admin'])),

                TextInput::make('quantity')
                    ->label(
                        __('messages.quantity')
                    )
                    ->numeric()
                    ->required()
                    ->hidden(fn (callable $get) => $get('movement_type') === 'adjustment'
                        && auth()->user()?->hasAnyRole(['Admin', 'Super Admin'])),

                Textarea::make('notes')
                    ->label(
                        __('messages.notes')
                    ),

            ]);
    }
}
