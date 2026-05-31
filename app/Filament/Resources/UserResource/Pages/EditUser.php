<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;

use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource =
        UserResource::class;

    protected function afterSave(): void
    {
        $roles = $this->data['roles'] ?? [];

        $permissions =
            $this->data['permissions'] ?? [];

        $this->record->syncRoles(
            $roles
        );

        $this->record->syncPermissions(
            $permissions
        );
    }
}