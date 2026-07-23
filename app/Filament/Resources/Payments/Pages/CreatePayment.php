<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\BankTransferPayment;
use App\Models\ChequePayment;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    /** Nested chequePayment/bankTransferPayment sub-arrays — not real Payment
     *  columns, so they must be pulled out before create() and persisted
     *  separately in afterCreate(). Without this the cheque/bank details
     *  typed into the form were silently discarded on save. */
    private ?array $pendingChequeData = null;
    private ?array $pendingBankTransferData = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingChequeData = $data['chequePayment'] ?? null;
        $this->pendingBankTransferData = $data['bankTransferPayment'] ?? null;

        unset($data['chequePayment'], $data['bankTransferPayment']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $payment = $this->record;

        if ($payment->payment_method === 'cheque' && filled($this->pendingChequeData['cheque_number'] ?? null)) {
            ChequePayment::create([
                'payment_id'    => $payment->id,
                'cheque_number' => $this->pendingChequeData['cheque_number'],
                'bank_name'     => $this->pendingChequeData['bank_name'] ?? null,
                'due_date'      => $this->pendingChequeData['due_date'] ?? null,
                'scan_path'     => $this->pendingChequeData['scan_path'] ?? null,
                // The form's single "status" field drives both Payment.status
                // and ChequePayment.status — they were a duplicated field before.
                'status'        => $payment->status ?: 'received',
            ]);
        }

        if ($payment->payment_method === 'bank_transfer' && filled($this->pendingBankTransferData['bank_name'] ?? null)) {
            BankTransferPayment::create([
                'payment_id'        => $payment->id,
                'bank_name'         => $this->pendingBankTransferData['bank_name'],
                'reference_number'  => $this->pendingBankTransferData['reference_number'] ?? null,
                'transfer_date'     => $this->pendingBankTransferData['transfer_date'] ?? null,
                'confirmation_file' => $this->pendingBankTransferData['confirmation_file'] ?? null,
                'status'            => $payment->status ?: 'sent',
            ]);
        }
    }
}
