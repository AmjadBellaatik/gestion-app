<?php

namespace App\Filament\Resources\MotorcycleUnits\Pages;

use App\Filament\Resources\MotorcycleUnits\MotorcycleUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMotorcycleUnits extends ListRecords
{
    protected static string $resource = MotorcycleUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
