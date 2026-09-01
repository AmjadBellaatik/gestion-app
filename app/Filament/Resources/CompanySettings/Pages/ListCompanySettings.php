<?php

namespace App\Filament\Resources\CompanySettings\Pages;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanySettings extends ListRecords
{
    protected static string $resource = CompanySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Visible only when CompanySettingResource::canCreate() is true
            // (CompanyPolicy::create → "Super Admin").
            CreateAction::make()
                ->label(__('messages.create_company')),
        ];
    }
}
