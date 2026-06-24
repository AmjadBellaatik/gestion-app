<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransfer extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
