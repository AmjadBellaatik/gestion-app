<?php

namespace App\Filament\Resources\Motorcycles\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Motorcycles\MotorcycleResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMotorcycle extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = MotorcycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
