<?php

namespace App\Services\Workshop;

use App\Models\RepairTicket;
use App\Services\Accounting\AccountingService;

class RepairService
{
    /*
    |--------------------------------------------------------------------------
    | Recalculate ticket totals from line items
    |--------------------------------------------------------------------------
    */

    public static function calculateTotals(RepairTicket $ticket): void
    {
        $partsCost = $ticket->items()->sum('total');
        $discount  = (float) $ticket->discount_amount;

        $ticket->update([
            'parts_cost' => $partsCost,
            'total_cost' => round((float) $ticket->labor_cost + $partsCost - $discount, 2),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete a repair — finalize costs and create the accounting entry.
    | Stock is NOT consumed here; parts are deducted via RepairItem::created
    | observer when each item is added to the ticket.
    |--------------------------------------------------------------------------
    */

    public static function complete(RepairTicket $ticket): void
    {
        self::calculateTotals($ticket);

        // Refresh after calculateTotals saved
        $ticket->refresh();

        try {
            AccountingService::createEntry([
                'company_id'  => $ticket->company_id,
                'reference'   => $ticket->ticket_number,
                'description' => 'Repair Ticket ' . $ticket->ticket_number,
                'lines'       => [
                    ['account_code' => '411100', 'debit'  => (float) $ticket->total_cost, 'credit' => 0],
                    ['account_code' => '706100', 'debit'  => 0, 'credit' => (float) $ticket->total_cost],
                ],
            ]);
        } catch (\Throwable) {
            // Accounting failures must never block repair completion
        }

        $ticket->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'finished_at'  => now(),
        ]);
    }
}
