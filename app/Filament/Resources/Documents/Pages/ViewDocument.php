<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Services\Documents\DocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_pdf')
                ->label(__('messages.regenerate'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('messages.regenerate'))
                ->modalDescription(__('messages.regenerate_document_confirm'))
                ->action(function (): void {
                    // Force-reload all relations so the PDF uses current DB data
                    $this->record->load([
                        'documentType',
                        'documentTemplate',
                        'company',
                        'client',
                        'supplier',
                        'sale',
                        'items.product',
                        'items.motorcycleUnit.motorcycleModel.brand',
                        'items.motorcycleUnit.motorcycleModel.homologation',
                    ]);

                    try {
                        DocumentService::generatePdfFor($this->record);

                        Notification::make()
                            ->title(__('messages.document_regenerated'))
                            ->success()
                            ->send();

                        $this->refreshFormData(['pdf_path', 'generated_at']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('messages.document_regeneration_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
