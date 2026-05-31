<?php

namespace App\Filament\Resources\Stock\Support;

use App\Models\Product;
use App\Models\MotorcycleModel;
use App\Models\MotorcycleUnit;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class StockActions
{
    public static function canAdjust(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }

    public static function productMovementForm(?int $productId = null): array
    {
        return [
            Select::make('product_id')
                ->label(__('messages.product'))
                ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->default($productId)
                ->disabled((bool) $productId)
                ->dehydrated()
                ->searchable()
                ->preload()
                ->required(),

            Select::make('warehouse_id')
                ->label(__('messages.warehouse'))
                ->options(fn () => Warehouse::active()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload(),

            TextInput::make('quantity')
                ->label(__('messages.quantity'))
                ->numeric()
                ->minValue(0.01)
                ->required(),

            Textarea::make('notes')
                ->label(__('messages.notes')),
        ];
    }

    public static function productAdjustmentForm(int $productId): array
    {
        return [
            Hidden::make('product_id')
                ->default($productId),

            Select::make('warehouse_id')
                ->label(__('messages.warehouse'))
                ->options(fn () => Warehouse::active()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload(),

            TextInput::make('quantity')
                ->label(__('messages.new_quantity'))
                ->numeric()
                ->minValue(0)
                ->required(),

            Textarea::make('notes')
                ->label(__('messages.notes')),
        ];
    }

    public static function motorcycleUnitForm(int $motorcycleModelId): array
    {
        return [
            Hidden::make('motorcycle_model_id')
                ->default($motorcycleModelId),

            Select::make('warehouse_id')
                ->label(__('messages.warehouse'))
                ->options(fn () => Warehouse::active()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload(),

            TextInput::make('chassis_number')
                ->label(__('messages.chassis_number'))
                ->required()
                ->unique(MotorcycleUnit::class, 'chassis_number'),

            TextInput::make('fabrication_number')
                ->label(__('messages.fabrication_number')),

            Textarea::make('notes')
                ->label(__('messages.notes')),
        ];
    }

    public static function addProductStock(array $data): void
    {
        $product = Product::query()->findOrFail($data['product_id']);

        StockMovement::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'movement_type' => 'purchase',
            'type' => 'entry',
            'quantity' => (float) $data['quantity'],
            'unit_cost' => 0,
            'reference' => 'STK-IN-'.now()->format('YmdHis'),
            'notes' => $data['notes'] ?? __('messages.stock_entry'),
            'user_id' => auth()->id(),
        ]);

        Notification::make()
            ->title(__('messages.stock_entry'))
            ->success()
            ->send();
    }

    public static function adjustProductStock(array $data): void
    {
        $product = Product::query()->findOrFail($data['product_id']);
        $warehouseId = $data['warehouse_id'] ?? null;
        $targetQuantity = (float) $data['quantity'];
        $currentQuantity = self::currentProductQuantity($product, $warehouseId);
        $delta = round($targetQuantity - $currentQuantity, 2);

        if ($delta === 0.0) {
            Notification::make()
                ->title(__('messages.no_change'))
                ->info()
                ->send();

            return;
        }

        StockMovement::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'movement_type' => 'adjustment',
            'type' => $delta > 0 ? 'entry' : 'exit',
            'quantity' => abs($delta),
            'unit_cost' => 0,
            'reference' => 'STK-ADJ-'.now()->format('YmdHis'),
            'notes' => $data['notes'] ?? __('messages.adjustment'),
            'user_id' => auth()->id(),
        ]);

        Notification::make()
            ->title(__('messages.adjustment'))
            ->success()
            ->send();
    }

    public static function addMotorcycleStock(array $data): void
    {
        $model = MotorcycleModel::query()->findOrFail($data['motorcycle_model_id']);

        $unit = MotorcycleUnit::create([
            'company_id' => session('company_id'),
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'motorcycle_model_id' => $model->id,
            'chassis_number' => $data['chassis_number'],
            'fabrication_number' => $data['fabrication_number'] ?? null,
            'status' => 'in_stock',
            'purchase_date' => now()->toDateString(),
        ]);

        StockMovement::create([
            'company_id' => $unit->company_id,
            'motorcycle_unit_id' => $unit->id,
            'warehouse_id' => $unit->warehouse_id,
            'movement_type' => 'purchase',
            'type' => 'entry',
            'quantity' => 1,
            'unit_cost' => 0,
            'reference' => 'MOTO-IN-'.now()->format('YmdHis'),
            'notes' => $data['notes'] ?? __('messages.stock_entry'),
            'user_id' => auth()->id(),
        ]);

        Notification::make()
            ->title(__('messages.stock_entry'))
            ->success()
            ->send();
    }

    public static function currentProductQuantity(Product $product, ?int $warehouseId = null): float
    {
        if (! $warehouseId) {
            return (float) $product->current_stock;
        }

        $query = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId);

        $in = (clone $query)
            ->where(function ($query): void {
                $query
                    ->whereIn('type', ['entry', 'in'])
                    ->orWhereIn('movement_type', ['purchase', 'adjustment', 'return']);
            })
            ->sum('quantity');

        $out = (clone $query)
            ->where(function ($query): void {
                $query
                    ->whereIn('type', ['exit', 'out'])
                    ->orWhereIn('movement_type', ['sale']);
            })
            ->sum('quantity');

        return (float) $in - (float) $out;
    }
}
