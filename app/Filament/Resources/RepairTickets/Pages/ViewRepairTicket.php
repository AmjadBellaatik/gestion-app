<?php

namespace App\Filament\Resources\RepairTickets\Pages;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRepairTicket extends ViewRecord
{
    protected static string $resource = RepairTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
