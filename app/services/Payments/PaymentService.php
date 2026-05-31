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

            // --- 2. Reverse ledger transaction ---
            $payment->transaction()->delete();

            // --- 3. Reverse treasury entry ---
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
                $sale->update([
                    'payment_status'   => 'unpaid',
                    'paid_amount'      => max(0, (float) $sale->paid_amount - (float) $payment->amount),
                    'remaining_amount' => min((float) $sale->total, (float) $sale->remaining_amount + (float) $payment->amount),
                ]);

                if ($sale->reseller_id && $sale->reseller) {
                    $sale->reseller->update([
                        'is_blocked'     => true,
                        'blocked_reason' => $blockedReason,
                        'credit_balance' => (float) $sale->reseller->credit_balance - (float) $payment->amount,
                        'current_debt'   => (float) $sale->reseller->current_debt   + (float) $payment->amount,
                    ]);
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
            $amount = (float) ($ticket->total_cost ?? 0);

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Cannot create a payment with zero amount.');
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

    private function updateSaleBalance(Sale $sale, float $amount): void
    {
        // Recompute from DB to avoid race-condition stale reads
        $totalPaid    = (float) $sale->payments()
            ->where('status', self::STATUS_PAID)
            ->sum('amount');
        $newRemaining = max(0, (float) $sale->total - $totalPaid);

        $sale->update([
            'paid_amount'      => $totalPaid,
            'remaining_amount' => $newRemaining,
            'payment_status'   => $newRemaining <= 0 ? 'paid' : 'partial',
        ]);
    }

    private function reverseSaleBalance(Sale $sale, float $amount): void
    {
        $newPaid      = max(0, (float) $sale->paid_amount - $amount);
        $newRemaining = max(0, (float) $sale->total - $newPaid);

        $sale->update([
            'paid_amount'      => $newPaid,
            'remaining_amount' => $newRemaining,
            'payment_status'   => $newPaid <= 0 ? 'unpaid' : 'partial',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Private: Repair Balance
    |--------------------------------------------------------------------------
    */

    private function updateRepairBalance(RepairTicket $ticket, float $amount): void
    {
        $total        = (float) ($ticket->total_cost ?? 0);
        $alreadyPaid  = (float) ($ticket->getAttribute('paid_amount') ?? 0);
        $newPaid      = $alreadyPaid + $amount;
        $newRemaining = max(0, $total - $newPaid);

        $data = ['payment_status' => $newRemaining <= 0 ? 'paid' : 'partial'];

        if ($newRemaining <= 0) {
            $data['paid_at'] = now();
        }

        $ticket->update($data);
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
        Transaction::create([
            'company_id'      => $payment->company_id,
            'type'            => $this->typeFor($payment),
            'category'        => $this->categoryFor($payment),
            'amount'          => $payment->amount,
            'direction'       => $this->directionFor($payment),
            'reference_type'  => Payment::class,
            'reference_id'    => $payment->id,
            'payment_method'  => $payment->payment_method,
            'status'          => $this->transactionStatusFor($payment),
            'description'     => $this->descriptionFor($payment),
            'transaction_date' => now(),
            'created_by'      => auth()->id(),
        ]);
    }

    private function createTreasuryTransaction(Payment $payment): void
    {
        if (TreasuryTransaction::where('payment_id', $payment->id)->exists()) {
            return;
        }

        TreasuryTransaction::create([
            'company_id'  => $payment->company_id,
            'payment_id'  => $payment->id,
            'type'        => 'entry',
            'amount'      => $payment->amount,
            'description' => $this->descriptionFor($payment),
        ]);
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
