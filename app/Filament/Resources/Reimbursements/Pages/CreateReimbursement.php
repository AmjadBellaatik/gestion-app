<?php

namespace App\Filament\Resources\Reimbursements\Pages;

use App\Filament\Resources\Reimbursements\ReimbursementResource;

use Filament\Resources\Pages\CreateRecord;

class CreateReimbursement extends CreateRecord
{
    protected static string $resource =
        ReimbursementResource::class;
}