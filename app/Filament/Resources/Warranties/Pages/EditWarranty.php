<?php

namespace App\Filament\Resources\Warranties\Pages;

use App\Filament\Resources\Warranties\WarrantyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarranty extends EditRecord
{
    protected static string $resource = WarrantyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
