<?php

namespace App\Filament\Resources\PurchaseItems\Pages;

use App\Filament\Resources\PurchaseItems\PurchaseItemResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseItem extends ViewRecord
{
    protected static string $resource = PurchaseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
