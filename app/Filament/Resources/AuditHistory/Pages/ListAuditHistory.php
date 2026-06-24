<?php

namespace App\Filament\Resources\AuditHistory\Pages;

use App\Filament\Resources\AuditHistory\AuditHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditHistory extends ListRecords
{
    protected static string $resource = AuditHistoryResource::class;

    public function getTitle(): string
    {
        return __('messages.audit_history');
    }
}
