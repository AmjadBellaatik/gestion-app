<?php

namespace App\Services\Stock;

use App\Models\PurchaseOrder;
use App\Models\StockMovement;

class PurchaseReceivingService
{
    public static function receive(
        PurchaseOrder $purchaseOrder
    ): void {

        foreach (
            $purchaseOrder->items
            as $item
        ) {

            StockMovement::create([

                'company_id' =>
                    $purchaseOrder->company_id,

                'product_id' =>
                    $item->product_id,

                'warehouse_id' =>
                    $purchaseOrder->warehouse_id,

                'type' =>
                    'purchase',

                'quantity' =>
                    $item->quantity,

                'reference_type' =>
                    PurchaseOrder::class,

                'reference_id' =>
                    $purchaseOrder->id,

                'notes' =>
                    'Purchase Order Reception',

            ]);

        }

        $purchaseOrder->update([

            'status' => 'received',

            'received_at' => now(),

        ]);
    }
}