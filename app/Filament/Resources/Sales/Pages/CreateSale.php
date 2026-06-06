<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;

use Filament\Resources\Pages\CreateRecord;

use App\Services\Sales\SaleService;

class CreateSale
    extends CreateRecord
{
    protected static string $resource =
        SaleResource::class;

    /**
     * Server-side enforcement of the sale_date permission rule (defense in depth;
     * the UI also disables the field for non-admins).
     *   - Non-admins: sale_date forced to today.
     *   - Everyone: future dates clamped to today (accounting cutoff).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $today = now()->toDateString();

        if (! SaleResource::isAdminUser()) {
            $data['sale_date'] = $today;
        } elseif (filled($data['sale_date'] ?? null) && $data['sale_date'] > $today) {
            $data['sale_date'] = $today;
        }

        $data['sale_date'] ??= $today;

        return $data;
    }

    protected function handleRecordCreation(
        array $data
    ): \Illuminate\Database\Eloquent\Model {

        return SaleService::create(
            $data
        );
    }
}