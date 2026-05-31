<?php

namespace App\Filament\Resources\RepairTickets\Pages;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use App\Models\DocumentType;
use App\Models\RepairTicket;
use App\Services\Documents\DocumentService;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRepairTicket extends EditRecord
{
    protected static string $resource = RepairTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | Generate Repair Report (PDF for client)
            |------------------------------------------------------------------
            */

            Action::make('generate_report')
                ->label(__('messages.generate_repair_report'))
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn (RepairTicket $record) => in_array($record->status, ['in_progress', 'completed', 'delivered']))
                ->action(function (RepairTicket $record): void {
                    // The RepairPrintController handles the actual PDF; this triggers generation
                    // and updates report_path on the record.
                    $url = route('repairs.print-order', $record);
                    Notification::make()
                        ->title(__('messages.repair_report_ready'))
                        ->body(__('messages.open_report_link'))
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('open')
                                ->label(__('messages.open'))
                                ->url($url)
                                ->openUrlInNewTab(),
                        ])
                        ->send();
                }),

            /*
            |------------------------------------------------------------------
            | Generate Invoice (via Document system)
            |------------------------------------------------------------------
            */

            Action::make('generate_invoice')
                ->label(__('messages.generate_invoice'))
                ->icon('heroicon-o-document-currency-dollar')
                ->color('success')
                ->visible(fn (RepairTicket $record) => in_array($record->status, ['completed', 'delivered'])
                    && ! $record->invoice_document_id)
                ->action(function (RepairTicket $record): void {

                    $record->recalculateCosts();

                    $documentTypeId = DocumentType::query()
                        ->where('code', 'INVOICE')
                        ->orWhere('code', 'REPAIR_INVOICE')
                        ->value('id');

                    if (! $documentTypeId) {
                        Notification::make()
                            ->title(__('messages.invoice_type_not_found'))
                            ->warning()
                            ->send();
                        return;
                    }

                    $document = DocumentService::generate([
                        'document_type_id' => $documentTypeId,
                        'client_id'        => $record->client_id,
                        'repair_ticket_id' => $record->id,
                        'language'         => app()->getLocale(),
                        'status'           => 'generated',
                        'subtotal'         => (float) $record->total_cost,
                        'tax'              => 0,
                        'total'            => (float) $record->total_cost,
                        'notes'            => 'Facture réparation ' . $record->ticket_number,
                    ]);

                    if ($document) {
                        $record->update(['invoice_document_id' => $document->id]);
                    }

                    Notification::make()
                        ->title(__('messages.invoice_generated'))
                        ->success()
                        ->send();
                }),

            /*
            |------------------------------------------------------------------
            | Validate Discount (admin / super admin only)
            |------------------------------------------------------------------
            */

            Action::make('validate_discount')
                ->label(__('messages.validate_discount'))
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->visible(fn (RepairTicket $record) => (auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false)
                    && (float) $record->discount_amount > 0
                    && ! $record->discount_validated)
                ->form([
                    Textarea::make('validation_note')
                        ->label(__('messages.validation_note'))
                        ->rows(2),
                ])
                ->action(function (RepairTicket $record, array $data): void {
                    $record->update([
                        'discount_validated'    => true,
                        'discount_validated_by' => auth()->id(),
                        'discount_validated_at' => now(),
                        'discount_note'         => $data['validation_note'] ?? $record->discount_note,
                    ]);
                    $record->recalculateCosts();

                    Notification::make()
                        ->title(__('messages.discount_validated'))
                        ->success()
                        ->send();
                }),

            /*
            |------------------------------------------------------------------
            | Recalculate Costs
            |------------------------------------------------------------------
            */

            Action::make('recalculate')
                ->label(__('messages.recalculate_costs'))
                ->icon('heroicon-o-calculator')
                ->color('gray')
                ->action(function (RepairTicket $record): void {
                    $record->recalculateCosts();
                    $this->refreshFormData(['parts_cost', 'total_cost']);

                    Notification::make()
                        ->title(__('messages.costs_recalculated'))
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['_linked_to_sale']);
        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->recalculateCosts();
    }
}
