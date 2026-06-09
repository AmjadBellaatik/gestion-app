<?php

namespace App\Services\Stock;

use App\Models\StockMovement;

class StockService
{
    public static function movement(array $data): StockMovement
    {
        return StockMovement::create([
            'company_id'         => $data['company_id'],
            'warehouse_id'       => $data['warehouse_id'] ?? null,
            'product_id'         => $data['product_id'] ?? null,
            'motorcycle_unit_id' => $data['motorcycle_unit_id'] ?? null,
            'type'               => $data['type'],
            'movement_type'      => $data['movement_type'] ?? null,
            'quantity'           => $data['quantity'],
            'unit_cost'          => $data['unit_cost'] ?? null,
            'reference'          => $data['reference'] ?? null,
            'reference_type'     => $data['reference_type'] ?? null,
            'reference_id'       => $data['reference_id'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'user_id'            => $data['user_id'] ?? auth()->id(),
        ]);
    }
}