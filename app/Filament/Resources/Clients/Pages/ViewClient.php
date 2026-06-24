<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Clients\ClientResource;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource =
        ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [

            EditAction::make(),
            DeleteAction::make()
                ->requiresConfirmation(),

        ];
    }
}
