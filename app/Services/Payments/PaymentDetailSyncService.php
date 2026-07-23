<?php

namespace App\Services\Payments;

use App\Models\BankTransferPayment;
use App\Models\ChequePayment;
use App\Models\Payment;

/**
 * Reconciles cheque/bank-transfer identifying details submitted from a
 * Sale's or RepairTicket's own page onto the linked Payment record.
 *
 * Deliberately narrow scope: this NEVER touches amount or status — those
 * stay under PaymentService's control (they drive the ledger/treasury and
 * motorcycle-unit-hold logic). It only corrects identifying details:
 *   - Same method as the existing payment → fix cheque/bank/reference typos.
 *   - Different method → reclassify the payment + swap its sub-record.
 * Use a dedicated "add payment" action for a genuinely new/additional payment.
 *
 * $submitted keys: payment_method, reference, cheque_number, bank_name,
 * cheque_due_date, transfer_reference, transfer_date.
 */
class PaymentDetailSyncService
{
    public static function sync(?Payment $payment, array $submitted): void
    {
        $method = $submitted['payment_method'] ?? null;

        if (! $payment || ! $method) {
            return;
        }

        $payment->loadMissing(['chequePayment', 'bankTransferPayment']);

        if ($payment->payment_method === $method) {
            if ($method === 'card' && filled($submitted['reference'] ?? null)) {
                $payment->update(['reference' => $submitted['reference']]);
            }

            if ($method === 'cheque' && $payment->chequePayment) {
                $payment->chequePayment->update([
                    'cheque_number' => $submitted['cheque_number'] ?? $payment->chequePayment->cheque_number,
                    'bank_name'     => $submitted['bank_name'] ?? $payment->chequePayment->bank_name,
                    'due_date'      => $submitted['cheque_due_date'] ?? $payment->chequePayment->due_date,
                ]);

                if (filled($submitted['cheque_number'] ?? null)) {
                    $payment->update(['reference' => $submitted['cheque_number']]);
                }
            }

            if ($method === 'bank_transfer' && $payment->bankTransferPayment) {
                $payment->bankTransferPayment->update([
                    'bank_name'        => $submitted['bank_name'] ?? $payment->bankTransferPayment->bank_name,
                    'reference_number' => $submitted['transfer_reference'] ?? $payment->bankTransferPayment->reference_number,
                    'transfer_date'    => $submitted['transfer_date'] ?? $payment->bankTransferPayment->transfer_date,
                ]);

                if (filled($submitted['transfer_reference'] ?? null)) {
                    $payment->update(['reference' => $submitted['transfer_reference']]);
                }
            }

            return;
        }

        // Method reclassified (e.g. cash -> cheque). Swap the sub-record type;
        // amount/status are left untouched.
        $payment->chequePayment?->delete();
        $payment->bankTransferPayment?->delete();

        $payment->update([
            'payment_method' => $method,
            'reference'      => match ($method) {
                'cheque'        => $submitted['cheque_number'] ?? null,
                'bank_transfer' => $submitted['transfer_reference'] ?? null,
                default         => $submitted['reference'] ?? null,
            },
        ]);

        if ($method === 'cheque' && filled($submitted['cheque_number'] ?? null)) {
            ChequePayment::create([
                'payment_id'    => $payment->id,
                'cheque_number' => $submitted['cheque_number'],
                'bank_name'     => $submitted['bank_name'] ?? null,
                'due_date'      => $submitted['cheque_due_date'] ?? null,
                'status'        => 'received',
            ]);
        }

        if ($method === 'bank_transfer' && filled($submitted['bank_name'] ?? null)) {
            BankTransferPayment::create([
                'payment_id'       => $payment->id,
                'bank_name'        => $submitted['bank_name'],
                'reference_number' => $submitted['transfer_reference'] ?? null,
                'transfer_date'    => $submitted['transfer_date'] ?? now()->toDateString(),
                'status'           => 'sent',
            ]);
        }
    }
}
