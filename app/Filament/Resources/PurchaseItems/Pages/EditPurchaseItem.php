<?php

namespace App\Filament\Resources\PurchaseItems\Pages;

use App\Filament\Resources\PurchaseItems\PurchaseItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseItem extends EditRecord
{
    protected static string $resource = PurchaseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
