<?php

namespace App\Filament\Resources\MotorcycleUnits\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\MotorcycleUnits\MotorcycleUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMotorcycleUnit extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = MotorcycleUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
