<?php

namespace App\Services\Workshop;

use App\Models\Product;
use App\Models\RepairItem;
use App\Models\RepairTicket;
use App\Models\StockMovement;

use App\Services\Accounting\AccountingService;

class RepairService
{
    /*
    |--------------------------------------------------------------------------
    | CONSUME PARTS
    |--------------------------------------------------------------------------
    */

    public static function consumeParts(
        RepairTicket $ticket
    ): void {

        foreach (

            $ticket->items

            as $item

        ) {

            /*
            |--------------------------------------------------------------------------
            | CREATE STOCK MOVEMENT
            |--------------------------------------------------------------------------
            */

            StockMovement::create([

                'company_id' =>

                    $ticket->company_id,

                'product_id' =>

                    $item->product_id,

                'type' => 'repair',

                'quantity' =>

                    $item->quantity,

                'reference_type' =>

                    RepairTicket::class,

                'reference_id' =>

                    $ticket->id,

                'notes' =>

                    'Repair Ticket: '

                    . $ticket->ticket_number,

                'created_by' =>

                    auth()->id(),

            ]);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE TOTALS
    |--------------------------------------------------------------------------
    */

    public static function calculateTotals(
        RepairTicket $ticket
    ): void {

        $partsCost =

            $ticket->items()

                ->sum('total');

        $ticket->update([

            'parts_cost' =>

                $partsCost,

            'total_cost' =>

                $partsCost

                + $ticket->labor_cost,

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE REPAIR
    |--------------------------------------------------------------------------
    */

    public static function complete(
        RepairTicket $ticket
    ): void {

        /*
        |--------------------------------------------------------------------------
        | STOCK MOVEMENTS
        |--------------------------------------------------------------------------
        */

        self::consumeParts(
            $ticket
        );

        /*
        |--------------------------------------------------------------------------
        | CALCULATE TOTALS
        |--------------------------------------------------------------------------
        */

        self::calculateTotals(
            $ticket
        );

        /*
        |--------------------------------------------------------------------------
        | ACCOUNTING AUTOMATION
        |--------------------------------------------------------------------------
        */

        AccountingService::createTransaction([

            'type' => 'repair',

            'amount' =>

                $ticket->total_cost,

            'direction' => 'income',

            'reference_type' =>

                RepairTicket::class,

            'reference_id' =>

                $ticket->id,

            'description' =>

                'Repair Ticket '

                . $ticket->ticket_number,

        ]);

        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        $ticket->update([

            'status' => 'completed',

        ]);

    }
}