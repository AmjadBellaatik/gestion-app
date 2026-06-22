<?php

namespace App\Filament\Resources\RepairTickets\Pages;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use App\Filament\Resources\RepairTickets\Schemas\RepairTicketForm;
use App\Models\DocumentType;
use App\Models\RepairTicket;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Workshop\RepairWorkflowService;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRepairTicket extends EditRecord
{
    protected static string $resource = RepairTicketResource::class;

    /*
    |------------------------------------------------------------------
    | Pre-fill the virtual _repair_source field so the form shows the
    | correct vehicle section when editing an existing record.
    |------------------------------------------------------------------
    */

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['sale_id'])) {
            $data['_repair_source'] = 'sale';
        } elseif (! empty($data['is_foreign_vehicle'])) {
            $data['_repair_source'] = 'foreign';
        } else {
            $data['_repair_source'] = 'stock';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Enforce source-driven rules authoritatively (type, warranty, mileage, labour)
        $data = RepairTicketForm::normalizeBySource($data);

        unset($data['_repair_source']);
        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->recalculateCosts();
    }

    protected function getHeaderActions(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | Advance workflow (non-admin one-step forward)
            |------------------------------------------------------------------
            */

            Action::make('advance_status')
                ->label(__('messages.advance_status'))
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
                ->visible(fn (RepairTicket $record) => $this->canAdvance($record))
                ->schema([
                    Textarea::make('notes')->label(__('messages.notes'))->rows(2),
                ])
                ->action(function (RepairTicket $record, array $data): void {
                    $next = $this->nextStatus($record->status);
                    if (! $next) {
                        return;
                    }

                    $userId = self::currentUserId();
                    $ok     = RepairWorkflowService::changeStatus($record, $next, $userId, $data['notes'] ?? null);

                    Notification::make()
                        ->title($ok ? __('messages.status_updated') : __('messages.status_transition_invalid'))
                        ->{$ok ? 'success' : 'warning'}()
                        ->send();

                    $this->refreshFormData(['status', 'payment_status']);
                }),

            /*
            |------------------------------------------------------------------
            | Admin force-override status
            |------------------------------------------------------------------
            */

            Action::make('force_status')
                ->label(__('messages.force_status'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->visible(fn () => self::isAdminUser())
                ->schema([
                    Select::make('new_status')
                        ->label(__('messages.status'))
                        ->options([
                            'open'             => __('messages.open'),
                            'diagnostic'       => __('messages.diagnostic'),
                            'waiting_approval' => __('messages.waiting_approval'),
                            'approved'         => __('messages.approved'),
                            'waiting_parts'    => __('messages.waiting_parts'),
                            'in_progress'      => __('messages.in_progress'),
                            'completed'        => __('messages.completed'),
                            'delivered'        => __('messages.delivered'),
                            'closed'           => __('messages.closed'),
                            'cancelled'        => __('messages.cancelled'),
                        ])
                        ->required(),
                    Textarea::make('notes')->label(__('messages.notes'))->rows(2),
                ])
                ->action(function (RepairTicket $record, array $data): void {
                    RepairWorkflowService::forceStatus(
                        $record,
                        $data['new_status'],
                        self::currentUserId(),
                        $data['notes'] ?? null
                    );

                    Notification::make()->title(__('messages.status_updated'))->success()->send();
                    $this->refreshFormData(['status', 'payment_status']);
                }),

            /*
            |------------------------------------------------------------------
            | Generate Repair Report (PDF)
            |------------------------------------------------------------------
            */

            Action::make('generate_report')
                ->label(__('messages.generate_repair_report'))
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn (RepairTicket $record) => in_array($record->status, [
                    'in_progress', 'completed', 'delivered', 'closed',
                ]))
                ->action(function (RepairTicket $record): void {
                    $url = route('repairs.print-order', $record);
                    Notification::make()
                        ->title(__('messages.repair_report_ready'))
                        ->body(__('messages.open_report_link'))
                        ->success()
                        ->actions([
                            Action::make('open')
                                ->label(__('messages.open'))
                                ->url($url)
                                ->openUrlInNewTab(),
                        ])
                        ->send();
                }),

            /*
            |------------------------------------------------------------------
            | Generate Invoice
            |------------------------------------------------------------------
            */

            Action::make('generate_invoice')
                ->label(__('messages.generate_invoice'))
                ->icon('heroicon-o-document-currency-dollar')
                ->color('success')
                ->visible(fn (RepairTicket $record) => in_array($record->status, ['completed', 'delivered', 'closed'])
                    && $record->repair_type !== 'internal'
                    && ! $record->invoice_document_id)
                ->action(function (RepairTicket $record): void {
                    $record->loadMissing(['items.product']);
                    $record->recalculateCosts();

                    $documentTypeId = DocumentType::where('code', DocumentType::INVOICE)->value('id');

                    if (! $documentTypeId) {
                        Notification::make()->title(__('messages.invoice_type_not_found'))->warning()->send();
                        return;
                    }

                    // Build one document line-item per repair item (HT prices)
                    $items = [];
                    foreach ($record->items as $item) {
                        $items[] = [
                            'description'     => $item->product?->name ?? __('messages.part'),
                            'item_type'       => 'product',
                            'quantity'        => (float) $item->quantity,
                            'unit_price'      => (float) $item->unit_price,
                            'discount_amount' => (float) $item->discount_amount,
                        ];
                    }
                    if ((float) $record->labor_cost > 0) {
                        $items[] = [
                            'description'     => __('messages.labor_cost'),
                            'item_type'       => 'service',
                            'quantity'        => 1,
                            'unit_price'      => (float) $record->labor_cost,
                            'discount_amount' => 0,
                        ];
                    }

                    $document = DocumentService::generate([
                        'document_type_id' => $documentTypeId,
                        'invoice_source'   => 'repair',
                        'client_id'        => $record->client_id,
                        'repair_ticket_id' => $record->getKey(),
                        'language'         => app()->getLocale(),
                        'status'           => 'generated',
                        'discount_amount'  => $record->discount_validated ? (float) $record->discount_amount : 0,
                        'items'            => $items,
                        'notes'            => 'Facture réparation ' . $record->ticket_number,
                    ]);

                    if ($document) {
                        $record->update(['invoice_document_id' => $document->id]);
                    }

                    Notification::make()->title(__('messages.invoice_generated'))->success()->send();
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
                ->visible(fn (RepairTicket $record) => self::isAdminUser()
                    && (float) $record->discount_amount > 0
                    && ! $record->discount_validated)
                ->schema([
                    Textarea::make('validation_note')->label(__('messages.validation_note'))->rows(2),
                ])
                ->action(function (RepairTicket $record, array $data): void {
                    $record->update([
                        'discount_validated'    => true,
                        'discount_validated_by' => self::currentUserId(),
                        'discount_validated_at' => now(),
                        'discount_note'         => $data['validation_note'] ?? $record->discount_note,
                    ]);
                    $record->recalculateCosts();
                    Notification::make()->title(__('messages.discount_validated'))->success()->send();
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
                    Notification::make()->title(__('messages.costs_recalculated'))->success()->send();
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    private function canAdvance(RepairTicket $record): bool
    {
        return $this->nextStatus($record->status) !== null && ! self::isAdminUser();
    }

    private function nextStatus(string $current): ?string
    {
        return match ($current) {
            'open'             => 'diagnostic',
            'diagnostic'       => 'waiting_approval',
            'waiting_approval' => 'approved',
            'approved'         => 'in_progress',
            'waiting_parts'    => 'in_progress',
            'in_progress'      => 'completed',
            'completed'        => 'delivered',
            'delivered'        => 'closed',
            default            => null,
        };
    }

    private static function isAdminUser(): bool
    {
        $user = request()->user();
        return $user instanceof User && $user->hasAnyRole(['Admin', 'Super Admin']);
    }

    private static function currentUserId(): ?int
    {
        $user = request()->user();
        if ($user === null) {
            return null;
        }
        return (int) $user->getAuthIdentifier();
    }
}
