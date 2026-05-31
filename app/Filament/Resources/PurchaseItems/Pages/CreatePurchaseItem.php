<?php

namespace App\Filament\Resources\PurchaseItems\Pages;

use App\Filament\Resources\PurchaseItems\PurchaseItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseItem extends CreateRecord
{
    protected static string $resource = PurchaseItemResource::class;
}
