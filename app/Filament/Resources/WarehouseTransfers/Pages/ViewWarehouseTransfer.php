<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWarehouseTransfer extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = WarehouseTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
