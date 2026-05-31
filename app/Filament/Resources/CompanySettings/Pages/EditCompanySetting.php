<?php

namespace App\Filament\Resources\CompanySettings\Pages;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use Filament\Resources\Pages\EditRecord;

class EditCompanySetting extends EditRecord
{
    protected static string $resource = CompanySettingResource::class;

    protected function afterSave(): void
    {
        $this->record->refresh();

        if ((int) session('company_id') !== (int) $this->record->getKey()) {
            return;
        }

        $this->redirect(
            CompanySettingResource::getUrl('edit', [
                'record' => $this->record,
            ]),
            navigate: false,
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
