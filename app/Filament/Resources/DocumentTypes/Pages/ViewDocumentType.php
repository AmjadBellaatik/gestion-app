<?php

namespace App\Filament\Resources\DocumentTypes\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentType extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
