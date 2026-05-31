<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected float $initialStock = 0;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialStock = (float) ($data['initial_stock'] ?? 0);

        unset($data['initial_stock']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->initialStock <= 0) {
            return;
        }

        /** @var Product $product */
        $product = $this->record;

        StockMovement::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'type' => 'entry',
            'movement_type' => 'purchase',
            'quantity' => $this->initialStock,
            'unit_cost' => (float) $product->purchase_price,
            'reference' => 'Initial stock',
            'notes' => 'Initial stock for '.$product->name,
            'user_id' => auth()->id(),
        ]);
    }
}
