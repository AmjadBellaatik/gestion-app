<?php

namespace App\Filament\Resources\MotorcycleUnits\Pages;

use App\Filament\Resources\MotorcycleUnits\MotorcycleUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMotorcycleUnit extends EditRecord
{
    protected static string $resource = MotorcycleUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
