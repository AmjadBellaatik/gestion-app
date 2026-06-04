<?php

namespace App\Services\Purchases;

use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Transaction;

use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public static function complete(
        Purchase $purchase
    ): void {

        DB::transaction(function () use (
            $purchase
        ) {

            $purchase->load([
                'items.product',
            ]);

            foreach (
                $purchase->items
                as $item
            ) {

                StockMovement::create([

                    'company_id' =>
                        $purchase->company_id,

                    'product_id' =>
                        $item->product_id,

                    'warehouse_id' =>
                        $purchase->warehouse_id,

                    'type'          => 'entry',
                    'movement_type' => 'purchase',

                    'quantity' =>
                        $item->quantity,

                    'reference_type' =>
                        Purchase::class,

                    'reference_id' =>
                        $purchase->id,

                    'notes' =>
                        'Purchase stock entry',

                ]);

            }

            Transaction::create([

                'company_id' =>
                    $purchase->company_id,

                'type' =>
                    'expense',

                'amount' =>
                    $purchase->total,

                'description' =>
                    'Purchase #' .
                    $purchase->reference,

                'reference_type' =>
                    Purchase::class,

                'reference_id' =>
                    $purchase->id,

            ]);

            $purchase->update([

                'status' =>
                    'completed',

                'completed_at' =>
                    now(),

            ]);

        });

    }
}