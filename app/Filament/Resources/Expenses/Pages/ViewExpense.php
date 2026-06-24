<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
