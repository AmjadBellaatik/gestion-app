<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\DocumentType;
use App\Services\Sales\SaleService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            DeleteAction::make()
                ->visible(fn () => SaleResource::isAdminUser()),

            ActionGroup::make([

                $this->regenerateDocumentAction(
                    DocumentType::INVOICE,
                    __('messages.regenerate_invoice')
                ),

                $this->regenerateDocumentAction(
                    DocumentType::DELIVERY_NOTE,
                    __('messages.delivery_note')
                ),

                $this->regenerateDocumentAction(
                    DocumentType::WARRANTY_CONTRACT,
                    __('messages.regenerate_warranty_contract')
                ),

                $this->regenerateDocumentAction(
                    DocumentType::CONFORMITY,
                    __('messages.regenerate_conformity_certificate')
                ),

            ])
                ->label(__('messages.documents'))
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->button(),
        ];
    }

    protected function regenerateDocumentAction(string $code, string $label): Action
    {
        return Action::make('regenerate_' . strtolower($code))
            ->label($label)
            ->icon('heroicon-o-arrow-path')
            ->visible(fn () => DocumentType::query()->where('code', $code)->where('is_active', true)->exists()
                && ! ($code === DocumentType::WARRANTY_CONTRACT && filled($this->record->reseller_id)))
            ->requiresConfirmation()
            ->action(function () use ($code): void {
                // Force-reload all sale relations so regeneration uses current DB data,
                // not stale Livewire-cached relations from earlier in the page session.
                $this->record->load([
                    'client',
                    'reseller',
                    'items.product',
                    'items.motorcycleUnit.motorcycleModel.brand',
                    'items.motorcycleUnit.motorcycleModel.homologation',
                    'documents.items',
                ]);

                try {
                    SaleService::generateSelectedDocumentsFromSale(
                        $this->record,
                        $this->record->items->all(),
                        [$code]
                    );

                    Notification::make()
                        ->title(__('messages.document_regenerated'))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title(__('messages.document_regeneration_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}
