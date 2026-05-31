<?php

namespace App\Filament\Resources\Reimbursements\Pages;

use App\Filament\Resources\Reimbursements\ReimbursementResource;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReimbursements extends ListRecords
{
    protected static string $resource =
        ReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Actions\CreateAction::make(),

        ];
    }
}