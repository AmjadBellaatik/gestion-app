<?php

namespace App\Filament\Resources\Reimbursements\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Reimbursements\ReimbursementResource;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReimbursement extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource =
        ReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}