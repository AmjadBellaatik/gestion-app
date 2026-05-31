<?php

namespace App\Filament\Resources\Warranties\Pages;

use App\Filament\Resources\Warranties\WarrantyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWarranty extends ViewRecord
{
    protected static string $resource = WarrantyResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
