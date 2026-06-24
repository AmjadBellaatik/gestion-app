<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
