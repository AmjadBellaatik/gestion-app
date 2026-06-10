<?php

namespace App\Filament\Resources\RepairTickets\Pages;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\RepairTicket;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Workshop\RepairWorkflowService;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRepairTicket extends ViewRecord
{
    protected static string $resource = RepairTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | Edit
            |------------------------------------------------------------------
            */

            EditAction::make(),

            /*
            |------------------------------------------------------------------
            | Register Payment
            |------------------------------------------------------------------
            */

            Action::make('register_payment')
                ->label(__('messages.register_payment'))
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->visible(fn (RepairTicket $record) => $record->payment_status !== 'paid'
                    && ! in_array($record->repair_type, ['warranty', 'internal'])
                    && (float) $record->total_cost > 0)
                ->schema([
                    TextInput::make('amount')
                        ->label(__('messages.amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(fn () => (float) $this->record->remaining_amount ?: (float) $this->record->total_cost)
                        ->default(fn () => (float) $this->record->remaining_amount ?: (float) $this->record->total_cost)
                        ->required(),

                    Select::make('payment_method')
                        ->label(__('messages.payment_method'))
                        ->options([
                            'cash'          => __('messages.cash'),
                            'card'          => __('messages.card'),
                            'cheque'        => __('messages.cheque'),
                            'bank_transfer' => __('messages.bank_transfer'),
                        ])
                        ->default('cash')
                        ->live()
                        ->required(),

                    TextInput::make('reference')
                        ->label(__('messages.reference'))
                        ->visible(fn ($get) => $get('payment_method') === 'card')
                        ->required(fn ($get) => $get('payment_method') === 'card'),

                    TextInput::make('cheque_number')
                        ->label(__('messages.cheque_number'))
                        ->visible(fn ($get) => $get('payment_method') === 'cheque')
                        ->required(fn ($get) => $get('payment_method') === 'cheque'),

                    Select::make('bank_name')
                        ->label(__('messages.bank_name'))
                        ->options([
                            'Attijariwafa Bank'               => 'Attijariwafa Bank',
                            'Banque Centrale Populaire (BCP)' => 'Banque Centrale Populaire (BCP)',
                            'Bank of Africa (BOA)'            => 'Bank of Africa (BOA)',
                            'CIH Bank'                        => 'CIH Bank',
                            'Al Barid Bank'                   => 'Al Barid Bank',
                            'Crédit Agricole du Maroc'        => 'Crédit Agricole du Maroc',
                            'Crédit du Maroc'                 => 'Crédit du Maroc',
                            'BMCI'                            => 'BMCI',
                            'CFG Bank'                        => 'CFG Bank',
                        ])
                        ->searchable()
                        ->visible(fn ($get) => in_array($get('payment_method'), ['cheque', 'bank_transfer']))
                        ->required(fn ($get) => in_array($get('payment_method'), ['cheque', 'bank_transfer'])),

                    DatePicker::make('cheque_due_date')
                        ->label(__('messages.due_date'))
                        ->visible(fn ($get) => $get('payment_method') === 'cheque')
                        ->required(fn ($get) => $get('payment_method') === 'cheque')
                        ->minDate(today()),

                    TextInput::make('transfer_reference')
                        ->label(__('messages.reference_number'))
                        ->visible(fn ($get) => $get('payment_method') === 'bank_transfer')
                        ->required(fn ($get) => $get('payment_method') === 'bank_transfer'),

                    DatePicker::make('transfer_date')
                        ->label(__('messages.transfer_date'))
                        ->visible(fn ($get) => $get('payment_method') === 'bank_transfer')
                        ->default(today()),

                    Textarea::make('notes')
                        ->label(__('messages.notes'))
                        ->rows(2),
                ])
                ->action(function (RepairTicket $record, array $data): void {
                    $method = $data['payment_method'];

                    $ref = match ($method) {
                        'card'          => $data['reference'] ?? null,
                        'cheque'        => implode(' | ', array_filter([
                            $data['cheque_number'] ?? null,
                            $data['bank_name'] ?? null,
                            isset($data['cheque_due_date']) ? 'Éch: ' . $data['cheque_due_date'] : null,
                        ])),
                        'bank_transfer' => implode(' | ', array_filter([
                            $data['bank_name'] ?? null,
                            $data['transfer_reference'] ?? null,
                            isset($data['transfer_date']) ? $data['transfer_date'] : null,
                        ])),
                        default => null,
                    };

                    Payment::create([
                        'company_id'       => $record->company_id,
                        'repair_ticket_id' => $record->id,
                        'client_id'        => $record->client_id,
                        'amount'           => (float) $data['amount'],
                        'payment_method'   => $method,
                        'reference'        => $ref,
                        'notes'            => $data['notes'] ?? null,
                    ]);

                    Notification::make()
                        ->title(__('messages.payment_registered'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['payment_status', 'paid_amount', 'remaining_amount', 'status']);
                }),

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

                    $ok = RepairWorkflowService::changeStatus($record, $next, self::currentUserId(), $data['notes'] ?? null);

                    Notification::make()
                        ->title($ok ? __('messages.status_updated') : __('messages.status_transition_invalid'))
                        ->{$ok ? 'success' : 'warning'}()
                        ->send();

                    $this->refreshFormData(['status', 'payment_status']);
                }),

            /*
            |------------------------------------------------------------------
            | Admin: force status
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
                    RepairWorkflowService::forceStatus($record, $data['new_status'], self::currentUserId(), $data['notes'] ?? null);
                    Notification::make()->title(__('messages.status_updated'))->success()->send();
                    $this->refreshFormData(['status', 'payment_status']);
                }),

            /*
            |------------------------------------------------------------------
            | Validate Discount (admin only)
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
            | Generate Repair Report
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
                            NotificationAction::make('open')
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
            | Recalculate Costs
            |------------------------------------------------------------------
            */

            Action::make('recalculate')
                ->label(__('messages.recalculate_costs'))
                ->icon('heroicon-o-calculator')
                ->color('gray')
                ->action(function (RepairTicket $record): void {
                    $record->recalculateCosts();
                    Notification::make()->title(__('messages.costs_recalculated'))->success()->send();
                    $this->refreshFormData(['parts_cost', 'total_cost', 'remaining_amount']);
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
        return $user ? (int) $user->getAuthIdentifier() : null;
    }
}
