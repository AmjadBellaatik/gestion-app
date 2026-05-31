<?php

namespace App\Services\Stock;

use App\Models\StockMovement;

class StockService
{
    public static function movement(
        array $data
    ): StockMovement {

        return StockMovement::create([

            'company_id' =>

                $data['company_id'],

            'warehouse_id' =>

                $data['warehouse_id']
                ?? null,

            'product_id' =>

                $data['product_id']
                ?? null,

            'motorcycle_unit_id' =>

                $data['motorcycle_unit_id']
                ?? null,

            'type' =>

                $data['type'],

            'quantity' =>

                $data['quantity'],

            'reference' =>

                $data['reference']
                ?? null,

            'notes' =>

                $data['notes']
                ?? null,

        ]);
    }
}