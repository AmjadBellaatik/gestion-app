<?php

namespace App\Services\Workshop;

use App\Models\BankTransferPayment;
use App\Models\ChequePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\RepairTicket;
use App\Models\Transaction;
use App\Services\Accounting\AccountingService;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;

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
    | observer (RepairStockService) when each item is added to the ticket.
    |--------------------------------------------------------------------------
    */

    public static function complete(RepairTicket $ticket): void
    {
        self::calculateTotals($ticket);

        // Refresh after calculateTotals saved
        $ticket->refresh();

        // Internal repairs are work on company inventory — they post an
        // inventory EXPENSE (never revenue) and create no customer payment.
        if ($ticket->repair_type === 'internal') {
            self::writeInternalExpense($ticket);

            $ticket->update([
                'status'       => 'completed',
                'completed_at' => now(),
                'finished_at'  => now(),
            ]);

            return;
        }

        self::writeJournalEntry($ticket);

        $ticket->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'finished_at'  => now(),
        ]);

        // Auto-create payment + transaction for paid repairs when none exists yet.
        // This mirrors the sale automation: Payment::creating sets status=paid (cash),
        // Payment::created → applyPayment() → creates Transaction + TreasuryTransaction.
        $ticket->refresh();

        if (
            ! in_array($ticket->repair_type, ['warranty', 'internal'], true)
            && (float) ($ticket->total_cost ?? 0) > 0
            && ! Payment::where('repair_ticket_id', $ticket->id)->exists()
        ) {
            try {
                PaymentService::createFromRepair($ticket, 'cash');
            } catch (\Throwable) {
                // Payment failures must not block repair completion
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal repair accounting — record an inventory EXPENSE, never revenue.
    | Mirrors the PurchaseService expense pattern (Transaction type=expense
    | referencing the ticket). Parts/consumables stock is already deducted via
    | the RepairItem observers (RepairStockService); this captures the monetary
    | cost. Idempotent per ticket: a re-completion updates the existing row.
    |--------------------------------------------------------------------------
    */

    public static function writeInternalExpense(RepairTicket $ticket): void
    {
        try {
            // No revenue entry must ever survive for an internal ticket.
            self::deleteJournalEntries($ticket);

            $amount = (float) $ticket->parts_cost;

            $existing = Transaction::withoutGlobalScopes()
                ->where('reference_type', RepairTicket::class)
                ->where('reference_id', $ticket->getKey())
                ->where('type', 'expense')
                ->first();

            if ($amount <= 0) {
                $existing?->delete();
                return;
            }

            if ($existing) {
                $existing->update(['amount' => $amount]);
                return;
            }

            Transaction::create([
                'company_id'     => $ticket->company_id,
                'type'           => 'expense',
                'amount'         => $amount,
                'description'    => 'Internal repair ' . $ticket->ticket_number,
                'reference_type' => RepairTicket::class,
                'reference_id'   => $ticket->getKey(),
                'transaction_date' => now(),
            ]);
        } catch (\Throwable) {
            // Accounting failures must never block the repair workflow
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Accounting — double-entry journal for the repair revenue.
    | Idempotent per ticket reference: any existing entry is removed and
    | rewritten with the current total so it always reflects live item changes.
    |--------------------------------------------------------------------------
    */

    public static function writeJournalEntry(RepairTicket $ticket): void
    {
        // Internal repairs never post revenue — redirect to the expense path so
        // item edits on a finalised internal ticket keep accounting correct.
        if ($ticket->repair_type === 'internal') {
            self::writeInternalExpense($ticket);
            return;
        }

        try {
            self::deleteJournalEntries($ticket);

            $total = (float) $ticket->total_cost;
            if ($total <= 0) {
                return;
            }

            AccountingService::createEntry([
                'company_id'  => $ticket->company_id,
                'reference'   => $ticket->ticket_number,
                'description' => 'Repair Ticket ' . $ticket->ticket_number,
                'lines'       => [
                    ['account_code' => '411100', 'debit' => $total, 'credit' => 0],
                    ['account_code' => '706100', 'debit' => 0, 'credit' => $total],
                ],
            ]);
        } catch (\Throwable) {
            // Accounting failures must never block the repair workflow
        }
    }

    /**
     * Re-sync the journal entry after item/total changes, but only once the
     * ticket has reached a finalised state (i.e. accounting already exists).
     * Before completion there is nothing to keep in sync.
     */
    public static function syncAccountingIfFinalized(RepairTicket $ticket): void
    {
        if (! in_array($ticket->status, ['completed', 'delivered', 'closed'], true)) {
            return;
        }

        $ticket->refresh();
        self::writeJournalEntry($ticket);
    }

    /*
    |--------------------------------------------------------------------------
    | Reverse ALL financial records tied to a repair (used on ticket delete).
    | Soft-deleting each Payment fires PaymentService::reversePayment, which
    | reverses the ledger Transaction, TreasuryTransaction and the repair
    | balance — exactly how sale deletion is handled. The repair's own journal
    | entry is then removed.
    |--------------------------------------------------------------------------
    */

    public static function reverseTicketFinancials(RepairTicket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $payments = Payment::withTrashed()
                ->withoutGlobalScopes()
                ->where('repair_ticket_id', $ticket->id)
                ->get();

            foreach ($payments as $payment) {
                ChequePayment::query()->where('payment_id', $payment->id)->delete();
                BankTransferPayment::query()->where('payment_id', $payment->id)->delete();

                if (! $payment->trashed()) {
                    // Fires Payment::deleting → reversePayment (ledger + treasury + balance)
                    $payment->delete();
                }
            }

            self::deleteJournalEntries($ticket);
        });
    }

    private static function deleteJournalEntries(RepairTicket $ticket): void
    {
        $entryIds = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $ticket->company_id)
            ->where('reference', $ticket->ticket_number)
            ->pluck('id');

        if ($entryIds->isEmpty()) {
            return;
        }

        JournalEntryLine::whereIn('journal_entry_id', $entryIds)->delete();
        JournalEntry::withoutGlobalScopes()->whereIn('id', $entryIds)->delete();
    }
}
