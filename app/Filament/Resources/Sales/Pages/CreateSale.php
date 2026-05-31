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

    protected function handleRecordCreation(
        array $data
    ): \Illuminate\Database\Eloquent\Model {

        return SaleService::create(
            $data
        );
    }
}