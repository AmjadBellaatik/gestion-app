<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseTransfer extends EditRecord
{
    protected static string $resource = WarehouseTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
