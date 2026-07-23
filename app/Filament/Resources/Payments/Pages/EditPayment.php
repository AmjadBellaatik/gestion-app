<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\BankTransferPayment;
use App\Models\ChequePayment;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    /** Nested chequePayment/bankTransferPayment sub-arrays captured for afterSave(). */
    private ?array $pendingChequeData = null;
    private ?array $pendingBankTransferData = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Explicitly load the real cheque/bank-transfer sub-record values into the
     * form. Filament's dot-notation fields (e.g. 'chequePayment.cheque_number')
     * only read from the relation if it happens to already be loaded on the
     * model — doing it here guarantees the edit form always shows what is
     * actually stored, not a blank/reset field.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $record->loadMissing(['chequePayment', 'bankTransferPayment']);

        if ($record->chequePayment) {
            $data['chequePayment'] = [
                'cheque_number' => $record->chequePayment->cheque_number,
                'bank_name'     => $record->chequePayment->bank_name,
                'due_date'      => optional($record->chequePayment->due_date)->toDateString(),
                'scan_path'     => $record->chequePayment->scan_path,
            ];
        }

        if ($record->bankTransferPayment) {
            $data['bankTransferPayment'] = [
                'bank_name'         => $record->bankTransferPayment->bank_name,
                'reference_number'  => $record->bankTransferPayment->reference_number,
                'transfer_date'     => optional($record->bankTransferPayment->transfer_date)->toDateString(),
                'confirmation_file' => $record->bankTransferPayment->confirmation_file,
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingChequeData = $data['chequePayment'] ?? null;
        $this->pendingBankTransferData = $data['bankTransferPayment'] ?? null;

        unset($data['chequePayment'], $data['bankTransferPayment']);

        return $data;
    }

    /**
     * Persist the sub-record for whichever method is now selected, and clean
     * up the other type if the method was switched away from it. This is what
     * was missing before — the nested fields were captured by the form but
     * never written anywhere, so editing cheque/bank details always appeared
     * to work until the next page load, when they'd be gone.
     */
    protected function afterSave(): void
    {
        $payment = $this->getRecord();
        $payment->loadMissing(['chequePayment', 'bankTransferPayment']);

        if ($payment->payment_method === 'cheque') {
            if (filled($this->pendingChequeData['cheque_number'] ?? null)) {
                $payment->chequePayment()->updateOrCreate(
                    ['payment_id' => $payment->id],
                    [
                        'cheque_number' => $this->pendingChequeData['cheque_number'],
                        'bank_name'     => $this->pendingChequeData['bank_name'] ?? null,
                        'due_date'      => $this->pendingChequeData['due_date'] ?? null,
                        'scan_path'     => $this->pendingChequeData['scan_path'] ?? $payment->chequePayment?->scan_path,
                        'status'        => $payment->status,
                    ]
                );
            }

            $payment->bankTransferPayment?->delete();
        } elseif ($payment->payment_method === 'bank_transfer') {
            if (filled($this->pendingBankTransferData['bank_name'] ?? null)) {
                $payment->bankTransferPayment()->updateOrCreate(
                    ['payment_id' => $payment->id],
                    [
                        'bank_name'         => $this->pendingBankTransferData['bank_name'],
                        'reference_number'  => $this->pendingBankTransferData['reference_number'] ?? null,
                        'transfer_date'     => $this->pendingBankTransferData['transfer_date'] ?? null,
                        'confirmation_file' => $this->pendingBankTransferData['confirmation_file'] ?? $payment->bankTransferPayment?->confirmation_file,
                        'status'            => $payment->status,
                    ]
                );
            }

            $payment->chequePayment?->delete();
        } else {
            // cash / card: neither sub-record applies — remove any stale one
            // left over from a previous method (e.g. cheque -> cash switch).
            $payment->chequePayment?->delete();
            $payment->bankTransferPayment?->delete();
        }
    }
}
