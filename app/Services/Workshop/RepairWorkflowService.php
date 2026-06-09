<?php

namespace App\Services\Workshop;

use App\Models\RepairStatusHistory;
use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RepairWorkflowService
{
    /*
    |--------------------------------------------------------------------------
    | Allowed automatic transitions per status.
    | Admin/SuperAdmin may bypass these via forceStatus().
    |--------------------------------------------------------------------------
    */

    private const TRANSITIONS = [
        'open'             => ['diagnostic', 'cancelled'],
        'diagnostic'       => ['waiting_approval', 'cancelled'],
        'waiting_approval' => ['approved', 'cancelled'],
        'approved'         => ['waiting_parts', 'in_progress', 'cancelled'],
        'waiting_parts'    => ['in_progress', 'cancelled'],
        'in_progress'      => ['completed', 'cancelled'],
        'completed'        => ['delivered'],
        'delivered'        => ['closed'],
        'closed'           => [],
        'cancelled'        => [],
    ];

    /*
    |--------------------------------------------------------------------------
    | Timestamp fields set when entering each status
    |--------------------------------------------------------------------------
    */

    private const TIMESTAMPS = [
        'diagnostic'       => 'diagnostic_at',
        'approved'         => 'assigned_at',
        'in_progress'      => 'started_at',
        'completed'        => ['finished_at', 'completed_at'],
        'delivered'        => 'delivered_at',
        'closed'           => 'closed_at',
        'cancelled'        => 'cancelled_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Advance status via the normal workflow (enforces allowed transitions).
    | Returns true on success, false if the transition is not allowed.
    |--------------------------------------------------------------------------
    */

    public static function changeStatus(
        RepairTicket $ticket,
        string $newStatus,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        $oldStatus = $ticket->status;
        $allowed   = self::TRANSITIONS[$oldStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            return false;
        }

        self::applyTransition($ticket, $oldStatus, $newStatus, $userId, $notes, false);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Admin/SuperAdmin force override — skips allowed-transition check.
    | The override is logged with a marker in the notes.
    |--------------------------------------------------------------------------
    */

    public static function forceStatus(
        RepairTicket $ticket,
        string $newStatus,
        ?int $userId = null,
        ?string $notes = null
    ): void {
        $user = request()->user();

        if (! ($user instanceof User) || ! $user->hasAnyRole(['Admin', 'Super Admin'])) {
            throw new AuthorizationException('Only admins can force repair status.');
        }

        $oldStatus    = $ticket->status;
        $overrideNote = '[MANUAL OVERRIDE] ' . ($notes ?? '');

        self::applyTransition($ticket, $oldStatus, $newStatus, $userId, $overrideNote, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Record the initial status when a ticket is first created.
    |--------------------------------------------------------------------------
    */

    public static function recordInitialStatus(RepairTicket $ticket, ?int $userId = null): void
    {
        RepairStatusHistory::create([
            'repair_ticket_id' => $ticket->getKey(),
            'old_status'       => null,
            'new_status'       => $ticket->status,
            'changed_by'       => $userId,
            'notes'            => 'Ticket created',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal: apply the transition, set timestamps, call service hooks.
    |--------------------------------------------------------------------------
    */

    private static function applyTransition(
        RepairTicket $ticket,
        string $oldStatus,
        string $newStatus,
        ?int $userId,
        ?string $notes,
        bool $isOverride
    ): void {
        $updates = ['status' => $newStatus];

        $tsField = self::TIMESTAMPS[$newStatus] ?? null;
        if ($tsField) {
            foreach ((array) $tsField as $field) {
                $updates[$field] = now();
            }
        }

        $ticket->update($updates);

        if ($newStatus === 'completed') {
            RepairService::complete($ticket);
        }

        RepairStatusHistory::create([
            'repair_ticket_id' => $ticket->getKey(),
            'old_status'       => $oldStatus,
            'new_status'       => $newStatus,
            'changed_by'       => $userId,
            'notes'            => $notes,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-transition triggered by step activity (called from RepairStep model)
    |--------------------------------------------------------------------------
    */

    public static function onStepStarted(RepairTicket $ticket): void
    {
        // Allow starting work from waiting_parts as well as approved
        if (in_array($ticket->status, ['approved', 'waiting_parts'], true)) {
            self::changeStatus($ticket, 'in_progress', null, 'Auto: first step started');
        }
    }

    public static function onAllStepsCompleted(RepairTicket $ticket): void
    {
        if ($ticket->status === 'in_progress') {
            self::changeStatus($ticket, 'completed', null, 'Auto: all steps done');
        }
    }
}
