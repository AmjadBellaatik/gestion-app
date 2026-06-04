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
                'company_id'     => $ticket->company_id,
                'product_id'     => $item->product_id,
                'type'           => 'exit',
                'movement_type'  => 'repair',
                'quantity'       => $item->quantity,
                'reference_type' => RepairTicket::class,
                'reference_id'   => $ticket->id,
                'notes'          => 'Repair Ticket: ' . $ticket->ticket_number,
                'created_by'     => auth()?->id(),
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

        try {
            AccountingService::createEntry([
                'company_id'  => $ticket->company_id,
                'reference'   => $ticket->ticket_number,
                'description' => 'Repair Ticket ' . $ticket->ticket_number,
                'lines'       => [
                    ['account_code' => '531100', 'debit' => (float) $ticket->total_cost, 'credit' => 0],
                    ['account_code' => '706100', 'debit' => 0, 'credit' => (float) $ticket->total_cost],
                ],
            ]);
        } catch (\Throwable) {
            // Accounting failures must never block repair completion.
        }

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