<?php

namespace App\Filament\Resources\MotorcycleModels\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\MotorcycleModels\MotorcycleModelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMotorcycleModel extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = MotorcycleModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
