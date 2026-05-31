<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;

use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource =
        UserResource::class;

    protected function afterCreate(): void
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