<?php

namespace App\Services\Workshop;

use App\Models\RepairTicket;
use App\Models\RepairStatusHistory;

use App\Services\Workshop\RepairService;

class RepairWorkflowService
{
    public static function changeStatus(
        RepairTicket $ticket,
        string $status,
        ?int $userId = null,
        ?string $notes = null
    ): void {

        $oldStatus =
            $ticket->status;

        $ticket->update([

            'status' => $status,

        ]);

        match ($status) {

            'diagnostic' =>

                $ticket->update([
                    'diagnostic_at' => now(),
                ]),

            'assigned' =>

                $ticket->update([
                    'assigned_at' => now(),
                ]),

            'finished' =>

                $ticket->update([
                    'finished_at' => now(),
                ]),

            'completed' => [

                $ticket->update([
                    'finished_at' => now(),
                ]),

                RepairService::complete(
                    $ticket
                )

            ],

            'paid' =>

                $ticket->update([
                    'paid_at' => now(),
                    'payment_status' => 'paid',
                ]),

            default => null,
        };

        RepairStatusHistory::create([

            'repair_ticket_id' =>
                $ticket->id,

            'old_status' =>
                $oldStatus,

            'new_status' =>
                $status,

            'changed_by' =>
                $userId,

            'notes' =>
                $notes,

        ]);
    }
}