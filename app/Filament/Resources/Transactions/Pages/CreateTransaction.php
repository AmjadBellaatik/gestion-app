<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected ?int $pendingSaleId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['type'] ?? null) === 'reimbursement') {
            $data['direction'] = 'out';
        }

        if (($data['type'] ?? null) === 'payment') {
            $data['direction'] = 'in';

            if (! empty($data['sale_id'])) {
                $this->pendingSaleId = (int) $data['sale_id'];
                $data['reference_type'] = Sale::class;
                $data['reference_id']   = $data['sale_id'];
            }
        }

        unset($data['sale_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->type !== 'payment' || ! $this->pendingSaleId) {
            return;
        }

        $sale = Sale::withoutGlobalScopes()->find($this->pendingSaleId);

        if (! $sale) {
            return;
        }

        Payment::create([
            'sale_id'        => $sale->id,
            'client_id'      => $sale->client_id,
            'amount'         => $this->record->amount,
            'payment_method' => $this->record->payment_method ?? 'cash',
            'status'         => 'paid',
        ]);
    }
}
