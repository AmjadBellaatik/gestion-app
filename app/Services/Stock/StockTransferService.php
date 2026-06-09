<?php

namespace App\Services\Stock;

use App\Models\StockTransfer;
use App\Services\Stock\StockService;

use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public static function validate(
        StockTransfer $transfer
    ): void {

        DB::transaction(function () use (
            $transfer
        ) {

            /*
            |--------------------------------------------------------------------------
            | Prevent Double Validation
            |--------------------------------------------------------------------------
            */

            if (

                $transfer->status ===
                'validated'

            ) {

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Loop Items
            |--------------------------------------------------------------------------
            */

            foreach (

                $transfer->items

                as $item

            ) {

                /*
                |--------------------------------------------------------------------------
                | PRODUCT TRANSFER
                |--------------------------------------------------------------------------
                */

                if (

                    $item->product_id

                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | خروج
                    |--------------------------------------------------------------------------
                    */

                    StockService::movement([
                        'company_id'    => $transfer->company_id,
                        'warehouse_id'  => $transfer->from_warehouse_id,
                        'product_id'    => $item->product_id,
                        'movement_type' => 'transfer',
                        'type'          => 'exit',
                        'quantity'      => $item->quantity,
                        'reference'     => $transfer->reference,
                        'notes'         => 'Warehouse transfer OUT',
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | دخول
                    |--------------------------------------------------------------------------
                    */

                    StockService::movement([
                        'company_id'    => $transfer->company_id,
                        'warehouse_id'  => $transfer->to_warehouse_id,
                        'product_id'    => $item->product_id,
                        'movement_type' => 'transfer',
                        'type'          => 'entry',
                        'quantity'      => $item->quantity,
                        'reference'     => $transfer->reference,
                        'notes'         => 'Warehouse transfer IN',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | MOTORCYCLE UNIT TRANSFER
                |--------------------------------------------------------------------------
                */

                if (

                    $item->motorcycle_unit_id

                ) {
                    StockService::movement([
                        'company_id'         => $transfer->company_id,
                        'warehouse_id'       => $transfer->from_warehouse_id,
                        'motorcycle_unit_id' => $item->motorcycle_unit_id,
                        'movement_type'      => 'transfer',
                        'type'               => 'exit',
                        'quantity'           => 1,
                        'reference'          => $transfer->reference,
                        'reference_type'     => StockTransfer::class,
                        'reference_id'       => $transfer->id,
                        'notes'              => 'Warehouse transfer OUT',
                    ]);

                    StockService::movement([
                        'company_id'         => $transfer->company_id,
                        'warehouse_id'       => $transfer->to_warehouse_id,
                        'motorcycle_unit_id' => $item->motorcycle_unit_id,
                        'movement_type'      => 'transfer',
                        'type'               => 'entry',
                        'quantity'           => 1,
                        'reference'          => $transfer->reference,
                        'reference_type'     => StockTransfer::class,
                        'reference_id'       => $transfer->id,
                        'notes'              => 'Warehouse transfer IN',
                    ]);

                    $item

                        ->motorcycleUnit

                        ->update([

                            'warehouse_id' =>

                                $transfer->to_warehouse_id,

                        ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Final Status
            |--------------------------------------------------------------------------
            */

            $transfer->update([

                'status' => 'validated',

            ]);
        });
    }
}
