<?php

namespace App\Filament\Resources\RepairTickets\Pages;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use App\Models\DocumentType;
use App\Services\Documents\DocumentService;
use Filament\Resources\Pages\CreateRecord;

class CreateRepairTicket extends CreateRecord
{
    protected static string $resource = RepairTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ticket_number is auto-generated in the model's creating hook
        unset($data['ticket_number'], $data['_linked_to_sale']);

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
                'repair_ticket_id' => $record->id,
                'language'         => app()->getLocale(),
                'status'           => 'generated',
                'subtotal'         => 0,
                'tax'              => 0,
                'total'            => 0,
                'notes'            => 'Ordre de réparation ' . $record->ticket_number,
            ]);
        }

        $record->recalculateCosts();
    }
}
