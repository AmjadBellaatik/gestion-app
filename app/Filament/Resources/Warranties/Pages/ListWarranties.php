<?php

namespace App\Filament\Resources\Warranties\Pages;

use App\Filament\Resources\Warranties\WarrantyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarranties extends ListRecords
{
    protected static string $resource = WarrantyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
