<?php

namespace App\Services\Repair;

use App\Models\User;
use App\Models\RepairTicket;
use App\Models\DocumentType;

use App\Services\Documents\DocumentService;
use App\Services\Stock\StockMovementService;
use App\Services\Accounting\TransactionService;
use App\Services\Notifications\NotificationService;

class RepairService
{
    public static function complete(
        RepairTicket $repairTicket
    ): RepairTicket {

        /*
        |--------------------------------------------------------------------------
        | CALCULATE TOTALS
        |--------------------------------------------------------------------------
        */

        $partsTotal =
            $repairTicket->items()
                ->sum('total');

        $laborTotal =
            $repairTicket->labor_cost ?? 0;

        $total =
            $partsTotal + $laborTotal;

        /*
        |--------------------------------------------------------------------------
        | UPDATE REPAIR
        |--------------------------------------------------------------------------
        */

        $repairTicket->update([

            'parts_total' =>
                $partsTotal,

            'labor_total' =>
                $laborTotal,

            'total' =>
                $total,

            'status' =>
                'completed',

            'completed_at' =>
                now(),

        ]);

        /*
        |--------------------------------------------------------------------------
        | UPDATE MOTORCYCLE UNIT MILEAGE
        |--------------------------------------------------------------------------
        */

        if (

            $repairTicket->motorcycleUnit

        ) {

            $repairTicket->motorcycleUnit

                ->update([

                    'mileage' =>

                        $repairTicket->mileage,

                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | REPAIR DURATION
        |--------------------------------------------------------------------------
        */

        $repairDuration = null;

        if (

            $repairTicket->repair_started_at

        ) {

            $repairDuration =

                $repairTicket
                    ->repair_started_at
                    ->diffInMinutes(now());

        }

        /*
        |--------------------------------------------------------------------------
        | STOCK MOVEMENTS
        |--------------------------------------------------------------------------
        */

        foreach (
            $repairTicket->items as $item
        ) {

            StockMovementService::create([

                'company_id' =>

                    $repairTicket->company_id,

                'product_id' =>

                    $item->product_id,

                'warehouse_id' =>

                    $repairTicket->warehouse_id,

                'type' =>

                    'repair',

                'quantity' =>

                    $item->quantity,

                'reference' =>

                    $repairTicket->ticket_number,

                'notes' =>

                    'Repair Parts Consumption',

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ACCOUNTING TRANSACTION
        |--------------------------------------------------------------------------
        */

        TransactionService::create([

            'company_id' =>

                $repairTicket->company_id,

            'type' =>

                'income',

            'amount' =>

                $total,

            'reference' =>

                $repairTicket->ticket_number,

            'description' =>

                'Repair Invoice',

            'transaction_date' =>

                now(),

        ]);

        /*
        |--------------------------------------------------------------------------
        | REPAIR INVOICE
        |--------------------------------------------------------------------------
        */

        DocumentService::generate([

            'document_type_id' =>

                DocumentType::where('code', 'REPAIR_INV')
                    ->where('is_active', true)
                    ->first()?->id,

            'client_id' =>

                $repairTicket->client_id,

            'repair_ticket_id' =>

                $repairTicket->id,

            'language' =>

                app()->getLocale(),

            'status' =>

                'generated',

            'subtotal' =>

                $partsTotal,

            'tax' =>

                0,

            'total' =>

                $total,

            'notes' =>

                'Repair Invoice',

        ]);

        /*
        |--------------------------------------------------------------------------
        | TECHNICIAN PERFORMANCE
        |--------------------------------------------------------------------------
        */

        if (
            $repairTicket->technician
        ) {

            $repairTicket
                ->technician
                ->increment(

                    'completed_repairs'
                );

            $repairTicket
                ->technician
                ->increment(

                    'generated_revenue',

                    $total
                );

            /*
            |--------------------------------------------------------------------------
            | AVERAGE REPAIR TIME
            |--------------------------------------------------------------------------
            */

            if (

                $repairDuration !== null

            ) {

                $technician =

                    $repairTicket->technician;

                $totalRepairs =

                    $technician->completed_repairs;

                $currentAverage =

                    $technician->average_repair_time ?? 0;

                $newAverage = (

                    ($currentAverage * ($totalRepairs - 1))

                    + $repairDuration

                ) / max($totalRepairs, 1);

                $technician->update([

                    'average_repair_time' =>

                        round($newAverage),

                    'last_repair_at' =>

                        now(),

                ]);

            }
        }

        /*
        |--------------------------------------------------------------------------
        | WARRANTY LINK
        |--------------------------------------------------------------------------
        */

        if (
            $repairTicket->is_warranty
        ) {

            DocumentService::generate([

                'document_type_id' =>

                    DocumentType::where('code', 'WAR_REPAIR')
                        ->where('is_active', true)
                        ->first()?->id,

                'client_id' =>

                    $repairTicket->client_id,

                'repair_ticket_id' =>

                    $repairTicket->id,

                'language' =>

                    app()->getLocale(),

                'status' =>

                    'generated',

                'subtotal' => 0,

                'tax' => 0,

                'total' => 0,

                'notes' =>

                    'Warranty Repair Report',

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | REPAIR COMPLETED NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        $users = User::permission(
            'manage_repairs'
        )->get();

        foreach ($users as $user) {

            NotificationService::send(

                $user,

                'Repair Completed',

                'Repair ticket ' .

                $repairTicket->ticket_number .

                ' has been completed.'

            );
        }

        return $repairTicket;
    }
}