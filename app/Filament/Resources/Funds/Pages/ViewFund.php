<?php

namespace App\Filament\Resources\Funds\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Funds\FundResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFund extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = FundResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
