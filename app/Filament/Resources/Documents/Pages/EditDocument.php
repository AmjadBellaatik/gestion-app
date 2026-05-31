<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Services\Documents\DocumentService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        app(DocumentService::class)->syncItems($record, $data['items'] ?? []);
        app(DocumentService::class)->recalculateTotals($record);
        app(DocumentService::class)->storePdf($record);

        return $record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        app(DocumentService::class)->recalculateTotals($this->record);
        app(DocumentService::class)->storePdf($this->record);

        $this->record->refresh();
    }
}
