<?php

namespace App\Services\Payments;

use App\Models\Client;
use App\Models\MotorcycleUnit;
use App\Models\Payment;
use App\Models\RepairTicket;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Transaction;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Notifications\GenericNotification;
use App\Services\Accounting\AccountingService;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /*
    |--------------------------------------------------------------------------
    | Status constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_PAID               = 'paid';
    public const STATUS_PENDING_VALIDATION = 'pending_validation';
    public const STATUS_PENDING            = 'pending';
    public const STATUS_REJECTED           = 'rejected';
    public const STATUS_CANCELLED          = 'cancelled';
    public const STATUS_BOUNCED            = 'bounced';

    /*
    |--------------------------------------------------------------------------
    | Apply Payment (validate a payment → credit balances + ledger entries)
    |--------------------------------------------------------------------------
    */

    public function applyPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {

            // --- 1. Credit linked sale balance ---
            if ($payment->sale_id) {
                $sale = Sale::withoutGlobalScopes()->find($payment->sale_id);
                if ($sale) {
                    $this->updateSaleBalance($sale, (float) $payment->amount);
                    // Motorcycle units linked to this sale can now be marked sold.
                    $this->transitionSaleMotorcycleUnits($sale, 'sold');
                }
            }

            // --- 2. Credit linked repair ticket balance ---
            if ($payment->repair_ticket_id) {
                $ticket = RepairTicket::withoutGlobalScopes()->find($payment->repair_ticket_id);
                if ($ticket) {
                    $this->updateRepairBalance($ticket, (float) $payment->amount);
                }
            }

            // --- 3. Ledger transaction (idempotent) ---
            if (! $payment->transaction()->exists()) {
                $this->createTransaction($payment);
            }

            // --- 4. Treasury entry for cash / card ---
            if (\in_array($payment->payment_method, ['cash', 'card'], true)) {
                $this->createTreasuryTransaction($payment);
            }

            // --- 5. Accounting journal ---
            $this->createAccountingEntry($payment);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Hold Motorcycle Units (pending payment created — put units on_hold)
    |--------------------------------------------------------------------------
    */

    public function holdLinkedMotorcycleUnits(Payment $payment): void
    {
        if (! $payment->sale_id) {
            return;
        }

        $sale = Sale::withoutGlobalScopes()->find($payment->sale_id);
        if (! $sale) {
            return;
        }

        $this->transitionSaleMotorcycleUnits($sale, 'on_hold');
    }

    /*
    |--------------------------------------------------------------------------
    | Reflect Pending Payment (cheque / bank_transfer created → update balance)
    |--------------------------------------------------------------------------
    */

    public function reflectPendingPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            if ($payment->sale_id) {
                $sale = Sale::withoutGlobalScopes()->find($payment->sale_id);
                if ($sale) {
                    $this->updateSaleBalance($sale, (float) $payment->amount);
                }
            }

            if ($payment->repair_ticket_id) {
                $ticket = RepairTicket::withoutGlobalScopes()->find($payment->repair_ticket_id);
                if ($ticket) {
                    $this->updateRepairBalance($ticket, (float) $payment->amount);
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reverse Payment (cancellation / rejection)
    |--------------------------------------------------------------------------
    */

    public function reversePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {

            // --- 1. Reverse sale balance ---
            if ($payment->sale_id) {
                $sale = Sale::withoutGlobalScopes()->find($payment->sale_id);
                if ($sale) {
                    $this->reverseSaleBalance($sale, (float) $payment->amount);
                    // If sale has no remaining valid payments, release the hold.
                    $hasPendingPayment = $sale->payments()
                        ->whereNotIn('status', [self::STATUS_REJECTED, self::STATUS_CANCELLED, 'canceled'])
                        ->where('id', '!=', $payment->id)
                        ->exists();

                    if (! $hasPendingPayment) {
                        $this->transitionSaleMotorcycleUnits($sale, 'in_stock');
                    }
                }
            }

            // --- 2. Reverse repair ticket balance ---
            if ($payment->repair_ticket_id) {
                $ticket = RepairTicket::withoutGlobalScopes()->find($payment->repair_ticket_id);
                if ($ticket) {
                    $this->reverseRepairBalance($ticket);
                }
            }

            // --- 3. Reverse ledger transaction ---
            $payment->transaction()->delete();

            // --- 4. Reverse treasury entry ---
            TreasuryTransaction::where('payment_id', $payment->id)->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Bounced Cheque
    |--------------------------------------------------------------------------
    */

    public function handleBouncedCheque(Payment $payment): void
    {
        $chequeRef   = $payment->reference ?? ('#' . $payment->id);
        $blockedReason = __('messages.blocked_bounced_cheque') . ' ' . $chequeRef;

        $payment->chequePayment?->update(['status' => 'bounced']);

        $notifyUsers = [];

        if ($payment->sale_id) {
            $sale = Sale::withoutGlobalScopes()
                ->with(['reseller', 'client'])
                ->find($payment->sale_id);

            if ($sale) {
                // Use the authoritative SUM-based reversal rather than stale-read arithmetic.
                $this->reverseSaleBalance($sale, (float) $payment->amount);

                if ($sale->reseller_id && $sale->reseller) {
                    // Atomic increment/decrement avoids stale-read race when two cheques
                    // for the same reseller bounce concurrently.
                    $sale->reseller->update([
                        'is_blocked'     => true,
                        'blocked_reason' => $blockedReason,
                    ]);
                    $sale->reseller->decrement('credit_balance', (float) $payment->amount);
                    $sale->reseller->increment('current_debt',   (float) $payment->amount);
                    if ($sale->reseller->email) {
                        $notifyUsers[] = $sale->reseller;
                    }
                }

                if ($sale->client_id && $sale->client) {
                    $sale->client->update([
                        'is_blocked'     => true,
                        'blocked_reason' => $blockedReason,
                        'balance'        => (float) $sale->client->balance - (float) $payment->amount,
                    ]);
                    if ($sale->client->email) {
                        $notifyUsers[] = $sale->client;
                    }
                }

                // Return motorcycle units to stock after bounced cheque
                $this->transitionSaleMotorcycleUnits($sale, 'in_stock');
            }
        }

        if ($payment->repair_ticket_id && ! $payment->sale_id) {
            $ticket = RepairTicket::withoutGlobalScopes()->with('client')->find($payment->repair_ticket_id);
            if ($ticket?->client) {
                $ticket->client->update([
                    'is_blocked'     => true,
                    'blocked_reason' => $blockedReason,
                    'balance'        => (float) $ticket->client->balance - (float) $payment->amount,
                ]);
                if ($ticket->client->email) {
                    $notifyUsers[] = $ticket->client;
                }
            }
        }

        $admins = User::role(['Admin', 'Super Admin'])->where('status', true)->get();
        $adminNotification = new GenericNotification(
            __('messages.cheque_returned_unpaid'),
            __('messages.sale_marked_unpaid') . ' ' . __('messages.cheque_number') . ': ' . $chequeRef
        );

        foreach ($admins as $admin) {
            try { $admin->notify($adminNotification); } catch (\Throwable) {}
        }

        $clientNotification = new GenericNotification(
            __('messages.cheque_returned_unpaid'),
            __('messages.blocked_bounced_cheque') . ' ' . $chequeRef
        );

        foreach ($notifyUsers as $notifiable) {
            try { $notifiable->notify($clientNotification); } catch (\Throwable) {}
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-create from Sale
    |--------------------------------------------------------------------------
    */

    public static function createFromSale(Sale $sale, string $paymentMethod = 'cash'): Payment
    {
        return DB::transaction(function () use ($sale, $paymentMethod) {
            $amount = (float) $sale->remaining_amount > 0
                ? (float) $sale->remaining_amount
                : (float) $sale->total;

            // Guard against zero-amount payments
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Cannot create a payment with zero amount.');
            }

            return Payment::create([
                'company_id'     => $sale->company_id,
                'sale_id'        => $sale->id,
                'client_id'      => $sale->client_id,
                'amount'         => $amount,
                'payment_method' => $paymentMethod,
                // status will be auto-set by the Payment::creating observer
                'reference'      => 'AUTO-' . $sale->sale_number,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-create from Repair Ticket
    |--------------------------------------------------------------------------
    */

    public static function createFromRepair(RepairTicket $ticket, string $paymentMethod = 'cash'): Payment
    {
        return DB::transaction(function () use ($ticket, $paymentMethod) {
            $remaining = (float) ($ticket->remaining_amount ?? 0);
            $amount    = $remaining > 0 ? $remaining : (float) ($ticket->total_cost ?? 0);

            if ($amount <= 0.00) {
                throw new \RuntimeException('Repair ticket has no outstanding balance.');
            }

            return Payment::create([
                'company_id'       => $ticket->company_id,
                'repair_ticket_id' => $ticket->id,
                'client_id'        => $ticket->client_id,
                'amount'           => $amount,
                'payment_method'   => $paymentMethod,
                // status auto-set by observer
                'reference'        => 'AUTO-' . $ticket->ticket_number,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy validate() — kept for backwards compatibility
    |--------------------------------------------------------------------------
    */

    public static function validate(Payment $payment): void
    {
        if ($payment->status === self::STATUS_PAID) {
            return;
        }

        $payment->update(['status' => self::STATUS_PAID]);
        // applyPayment fires via the updated() observer
    }

    /*
    |--------------------------------------------------------------------------
    | Private: Sale Balance
    |--------------------------------------------------------------------------
    */

    /*
     * Active statuses: anything that is not yet rejected, cancelled, or bounced.
     * Pending-validation cheques/transfers are committed funds — they should
     * immediately reduce the remaining amount on the sale.
     */
    private const TERMINAL_STATUSES = [
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        'canceled',
        self::STATUS_BOUNCED,
    ];

    private function updateSaleBalance(Sale $sale, float $amount): void
    {
        DB::transaction(function () use ($sale) {
            $locked = Sale::withoutGlobalScopes()->lockForUpdate()->findOrFail($sale->id);

            $totalPaid    = (float) $locked->payments()
                ->whereNotIn('status', self::TERMINAL_STATUSES)
                ->sum('amount');
            $newRemaining = max(0, (float) $locked->total - $totalPaid);

            $locked->update([
                'paid_amount'      => $totalPaid,
                'remaining_amount' => $newRemaining,
                'payment_status'   => $newRemaining <= 0 ? 'paid' : 'partial',
            ]);
        });
    }

    private function reverseSaleBalance(Sale $sale, float $amount): void
    {
        DB::transaction(function () use ($sale) {
            $locked = Sale::withoutGlobalScopes()->lockForUpdate()->findOrFail($sale->id);

            $totalPaid    = (float) $locked->payments()
                ->whereNotIn('status', self::TERMINAL_STATUSES)
                ->sum('amount');
            $newRemaining = max(0, (float) $locked->total - $totalPaid);

            $locked->update([
                'paid_amount'      => $totalPaid,
                'remaining_amount' => $newRemaining,
                'payment_status'   => $totalPaid <= 0 ? 'unpaid' : ($newRemaining <= 0 ? 'paid' : 'partial'),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Private: Repair Balance
    |--------------------------------------------------------------------------
    */

    private function updateRepairBalance(RepairTicket $ticket, float $amount): void
    {
        DB::transaction(function () use ($ticket) {
            $locked = RepairTicket::withoutGlobalScopes()->lockForUpdate()->findOrFail($ticket->id);

            $total        = (float) ($locked->total_cost ?? 0);
            $totalPaid    = (float) $locked->payments()
                ->whereNotIn('status', self::TERMINAL_STATUSES)
                ->sum('amount');
            $newRemaining = max(0, $total - $totalPaid);
            $newStatus    = $newRemaining <= 0 ? 'paid' : 'partial';

            if ($totalPaid > ($total + 0.01)) {
                throw new \RuntimeException(
                    sprintf('Payment exceeds repair total cost of %s.', $locked->total_cost)
                );
            }

            $locked->update([
                'paid_amount'      => $totalPaid,
                'remaining_amount' => $newRemaining,
                'payment_status'   => $newStatus,
                'paid_at'          => $newStatus === 'paid' ? now() : null,
            ]);

            if ($newStatus === 'paid' && $locked->invoice_document_id) {
                \App\Models\Document::where('id', $locked->invoice_document_id)
                    ->update(['status' => 'paid']);
            }
        });
    }

    private function reverseRepairBalance(RepairTicket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $locked = RepairTicket::withoutGlobalScopes()->lockForUpdate()->findOrFail($ticket->id);

            $total        = (float) ($locked->total_cost ?? 0);
            $totalPaid    = (float) $locked->payments()
                ->whereNotIn('status', self::TERMINAL_STATUSES)
                ->sum('amount');
            $newRemaining = max(0, $total - $totalPaid);
            $newStatus    = $totalPaid <= 0 ? 'unpaid' : ($newRemaining <= 0 ? 'paid' : 'partial');

            $locked->update([
                'paid_amount'      => $totalPaid,
                'remaining_amount' => $newRemaining,
                'payment_status'   => $newStatus,
                'paid_at'          => $newStatus === 'paid' ? now() : null,
            ]);

            if ($newStatus !== 'paid' && $locked->invoice_document_id) {
                \App\Models\Document::where('id', $locked->invoice_document_id)
                    ->update(['status' => 'generated']);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Private: Motorcycle Unit Status Transitions
    |--------------------------------------------------------------------------
    */

    private function transitionSaleMotorcycleUnits(Sale $sale, string $status): void
    {
        $unitIds = SaleItem::where('sale_id', $sale->id)
            ->whereNotNull('motorcycle_unit_id')
            ->pluck('motorcycle_unit_id');

        if ($unitIds->isEmpty()) {
            return;
        }

        $updateData = ['status' => $status];

        if ($status === 'sold') {
            $updateData['client_id'] = $sale->client_id;
            $updateData['sale_date'] = now()->toDateString();
        } elseif ($status === 'in_stock') {
            $updateData['client_id'] = null;
            $updateData['sale_date'] = null;
        }

        MotorcycleUnit::withoutGlobalScopes()
            ->whereIn('id', $unitIds)
            ->update($updateData);
    }

    /*
    |--------------------------------------------------------------------------
    | Private: Ledger / Treasury / Accounting
    |--------------------------------------------------------------------------
    */

    private function createTransaction(Payment $payment): void
    {
        // firstOrCreate on reference_type+reference_id is idempotent against
        // the TOCTOU race that the outer exists() check cannot prevent on its own.
        Transaction::firstOrCreate(
            [
                'reference_type' => Payment::class,
                'reference_id'   => $payment->id,
            ],
            [
                'company_id'       => $payment->company_id,
                'type'             => $this->typeFor($payment),
                'category'         => $this->categoryFor($payment),
                'amount'           => $payment->amount,
                'direction'        => $this->directionFor($payment),
                'payment_method'   => $payment->payment_method,
                'status'           => $this->transactionStatusFor($payment),
                'description'      => $this->descriptionFor($payment),
                'transaction_date' => now(),
                'created_by'       => auth()->id(),
            ]
        );
    }

    private function createTreasuryTransaction(Payment $payment): void
    {
        // firstOrCreate replaces the exists()+create() TOCTOU race condition.
        TreasuryTransaction::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'company_id'  => $payment->company_id,
                'type'        => 'entry',
                'amount'      => $payment->amount,
                'description' => $this->descriptionFor($payment),
            ]
        );
    }

    private function createAccountingEntry(Payment $payment): void
    {
        try {
            AccountingService::createEntry([
                'company_id'  => $payment->company_id,
                'reference'   => $payment->reference ?? ('PAY-' . $payment->id),
                'description' => $this->descriptionFor($payment),
                'lines'       => [
                    ['account_code' => '531100', 'debit' => $payment->amount, 'credit' => 0],
                    ['account_code' => '411100', 'debit' => 0, 'credit' => $payment->amount],
                ],
            ]);
        } catch (\Throwable) {
            // Accounting failures must never block payment processing.
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Private: Helpers
    |--------------------------------------------------------------------------
    */

    private function typeFor(Payment $payment): string
    {
        if ($payment->repair_ticket_id) {
            return 'repair_payment';
        }

        return 'sale_payment';
    }

    private function categoryFor(Payment $payment): string
    {
        if ($payment->sale_id) {
            return 'sale_payment';
        }
        if ($payment->repair_ticket_id) {
            return 'repair_payment';
        }

        return 'other_payment';
    }

    private function directionFor(Payment $payment): string
    {
        // Income: sale payments, repair payments, reimbursements received.
        // Expense: supplier payments, refunds issued.
        // Payments always represent money coming IN at the sale/repair level.
        return 'income';
    }

    private function transactionStatusFor(Payment $payment): string
    {
        return match ($payment->payment_method) {
            'cash', 'card' => 'validated',
            default        => 'pending',
        };
    }

    private function descriptionFor(Payment $payment): string
    {
        if ($payment->sale_id) {
            return 'Payment for sale #' . $payment->sale_id;
        }
        if ($payment->repair_ticket_id) {
            return 'Payment for repair #' . $payment->repair_ticket_id;
        }

        return 'Payment #' . $payment->id;
    }
}
