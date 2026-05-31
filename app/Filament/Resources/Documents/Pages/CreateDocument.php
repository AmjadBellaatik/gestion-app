<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Services\Documents\DocumentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['company_id'] = session('company_id');

        return app(DocumentService::class)->create($data);
    }

    protected function afterCreate(): void
    {
        app(DocumentService::class)->recalculateTotals($this->record);
        app(DocumentService::class)->storePdf($this->record);

        $this->record->refresh();
    }
}
