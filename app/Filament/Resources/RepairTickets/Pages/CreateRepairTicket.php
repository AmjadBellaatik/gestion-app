<?php

namespace App\Filament\Resources\RepairTickets\Pages;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use App\Filament\Resources\RepairTickets\Schemas\RepairTicketForm;
use App\Models\DocumentType;
use App\Services\Documents\DocumentService;
use App\Services\Workshop\RepairWorkflowService;
use Filament\Resources\Pages\CreateRecord;

class CreateRepairTicket extends CreateRecord
{
    protected static string $resource = RepairTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Enforce source-driven rules authoritatively (type, warranty, mileage, labour)
        $data = RepairTicketForm::normalizeBySource($data);

        // Remove virtual fields — not persisted on the model
        unset($data['ticket_number'], $data['_repair_source']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        $typeCode = $record->is_warranty ? 'REPAIR_WARRANTY' : 'REPAIR_ORDER';

        $documentTypeId = DocumentType::query()
            ->where('code', $typeCode)
            ->orWhere('code', 'REPAIR_ORDER')
            ->value('id');

        if ($documentTypeId) {
            DocumentService::generate([
                'document_type_id' => $documentTypeId,
                'client_id'        => $record->client_id,
                'repair_ticket_id' => $record->getKey(),
                'language'         => app()->getLocale(),
                'status'           => 'generated',
                'subtotal'         => 0,
                'tax'              => 0,
                'total'            => 0,
                'notes'            => 'Ordre de réparation ' . $record->ticket_number,
            ]);
        }

        $record->recalculateCosts();

        RepairWorkflowService::recordInitialStatus($record, self::currentUserId());
    }

    private static function currentUserId(): ?int
    {
        return (int)(request()->user()?->getAuthIdentifier() ?? 0) ?: null;
    }
}
