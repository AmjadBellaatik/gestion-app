<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Stock\StockService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected float $initialStock = 0;

    protected ?int $initialWarehouseId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialStock = (float) ($data['initial_stock'] ?? 0);
        $this->initialWarehouseId = ! empty($data['initial_warehouse_id'])
            ? (int) $data['initial_warehouse_id']
            : null;

        unset($data['initial_stock'], $data['initial_warehouse_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->initialStock <= 0) {
            return;
        }

        if (! $this->initialWarehouseId) {
            throw new \InvalidArgumentException(
                'warehouse_id is required when creating a product with initial stock.'
            );
        }

        /** @var Product $product */
        $product = $this->record;

        StockService::movement([
            'company_id'    => $product->company_id,
            'product_id'    => $product->id,
            'warehouse_id'  => $this->initialWarehouseId,
            'type'          => 'entry',
            'movement_type' => 'purchase',
            'quantity'      => $this->initialStock,
            'unit_cost'     => (float) $product->purchase_price,
            'reference'     => 'Initial stock',
            'notes'         => 'Initial stock for ' . $product->name,
        ]);
    }
}
