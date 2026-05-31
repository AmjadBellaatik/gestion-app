<?php

namespace App\Filament\Resources\Technicians\Pages;

use App\Filament\Resources\Technicians\TechnicianResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTechnician extends ViewRecord
{
    protected static string $resource = TechnicianResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
