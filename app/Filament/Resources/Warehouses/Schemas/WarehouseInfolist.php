<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Models\MotorcycleUnit;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WarehouseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.warehouse'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('messages.name'))
                            ->placeholder('-'),

                        TextEntry::make('code')
                            ->label(__('messages.code'))
                            ->placeholder('-'),

                        TextEntry::make('phone')
                            ->label(__('messages.phone'))
                            ->placeholder('-'),

                        TextEntry::make('is_active')
                            ->label(__('messages.is_active'))
                            ->badge()
                            ->formatStateUsing(
                                fn ($state) => $state
                                    ? __('messages.active')
                                    : __('messages.inactive')
                            )
                            ->color(
                                fn ($state) => $state ? 'success' : 'gray'
                            ),

                        TextEntry::make('address')
                            ->label(__('messages.address'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label(__('messages.created_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label(__('messages.updated_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make(__('messages.users'))
                    ->schema([
                        TextEntry::make('users.name')
                            ->hiddenLabel()
                            ->badge()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('messages.motorcycle_units'))
                    ->schema([
                        TextEntry::make('warehouse_motorcycle_units')
                            ->hiddenLabel()
                            ->state(function (Warehouse $record) {
                                $units = MotorcycleUnit::withoutGlobalScopes()
                                    ->where('warehouse_id', $record->id)
                                    ->with('motorcycleModel')
                                    ->orderByDesc('id')
                                    ->get();

                                if ($units->isEmpty()) {
                                    return '-';
                                }

                                $rows = $units->map(function (MotorcycleUnit $unit) {
                                    $model = trim(($unit->motorcycleModel?->marque ? $unit->motorcycleModel->marque . ' ' : '') . ($unit->motorcycleModel?->modele ?? __('messages.motorcycle')));
                                    $chassis = e($unit->chassis_number ?? '—');
                                    $statusColor = match ($unit->status) {
                                        'available', 'in_stock' => '#16a34a',
                                        'sold' => '#64748b',
                                        'reserved' => '#d97706',
                                        default => '#6b7280',
                                    };
                                    $statusLabel = e(ucfirst(str_replace('_', ' ', $unit->status ?? '')));
                                    return '<div style="padding:8px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">'
                                        . '<span><strong>' . e($model) . '</strong> <span style="color:#64748b;font-size:0.85em;">' . $chassis . '</span></span>'
                                        . '<span style="color:' . $statusColor . ';font-weight:600;font-size:0.85em;">' . $statusLabel . '</span>'
                                        . '</div>';
                                })->implode('');

                                return new HtmlString($rows);
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('messages.stock'))
                    ->schema([
                        TextEntry::make('warehouse_stock')
                            ->hiddenLabel()
                            ->state(function (Warehouse $record) {
                                $movements = StockMovement::withoutGlobalScopes()
                                    ->where('warehouse_id', $record->id)
                                    ->whereNotNull('product_id')
                                    ->with('product')
                                    ->get();

                                if ($movements->isEmpty()) {
                                    return '-';
                                }

                                $stockByProduct = [];
                                foreach ($movements as $movement) {
                                    $productId = $movement->product_id;
                                    if (! isset($stockByProduct[$productId])) {
                                        $stockByProduct[$productId] = [
                                            'name' => $movement->product?->name ?? '#' . $productId,
                                            'sku'  => $movement->product?->sku ?? '',
                                            'qty'  => 0,
                                        ];
                                    }
                                    if (in_array($movement->type, ['entry', 'in'], true)
                                        || in_array($movement->movement_type, ['purchase', 'adjustment', 'return'], true)) {
                                        $stockByProduct[$productId]['qty'] += (float) $movement->quantity;
                                    } else {
                                        $stockByProduct[$productId]['qty'] -= (float) $movement->quantity;
                                    }
                                }

                                $rows = array_map(function ($item) {
                                    $qty = number_format($item['qty'], 2, ',', ' ');
                                    $color = $item['qty'] > 0 ? '#16a34a' : '#dc2626';
                                    return '<div style="padding:8px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;">'
                                        . '<span><strong>' . e($item['name']) . '</strong> <span style="color:#64748b;font-size:0.85em;">' . e($item['sku']) . '</span></span>'
                                        . '<span style="color:' . $color . ';font-weight:600;">' . $qty . '</span>'
                                        . '</div>';
                                }, array_values($stockByProduct));

                                return new HtmlString(implode('', $rows));
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
