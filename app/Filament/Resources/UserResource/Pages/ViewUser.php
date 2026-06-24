<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UserResource::resetPasswordAction()
                ->record(fn () => $this->record),

            EditAction::make(),
        ];
    }
}
