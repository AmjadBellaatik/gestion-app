<?php

namespace App\Filament\Resources\RepairTickets\Pages;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepairTickets extends ListRecords
{
    protected static string $resource = RepairTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
