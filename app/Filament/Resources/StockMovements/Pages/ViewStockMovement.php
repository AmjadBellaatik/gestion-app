<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\StockMovements\StockMovementResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStockMovement extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
