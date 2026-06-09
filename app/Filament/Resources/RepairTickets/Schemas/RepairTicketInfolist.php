<?php

namespace App\Filament\Resources\RepairTickets\Schemas;

use App\Models\RepairTicket;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RepairTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /*
            |------------------------------------------------------------------
            | Ticket Header
            |------------------------------------------------------------------
            */

            Section::make(__('messages.ticket_information'))
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('ticket_number')
                            ->label(__('messages.ticket_number'))
                            ->weight('bold'),

                        TextEntry::make('repair_type')
                            ->label(__('messages.repair_type'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'warranty' => 'warning',
                                'paid'     => 'success',
                                'internal' => 'info',
                                default    => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'paid'))),

                        TextEntry::make('priority')
                            ->label(__('messages.priority'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'urgent' => 'danger',
                                'high'   => 'warning',
                                'normal' => 'info',
                                default  => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'normal'))),

                        TextEntry::make('status')
                            ->label(__('messages.status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'open'             => 'gray',
                                'diagnostic'       => 'warning',
                                'waiting_approval' => 'warning',
                                'approved'         => 'info',
                                'waiting_parts'    => 'info',
                                'in_progress'      => 'primary',
                                'completed'        => 'success',
                                'delivered'        => 'success',
                                'closed'           => 'success',
                                'cancelled'        => 'danger',
                                default            => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'open'))),
                    ]),
                ]),

            /*
            |------------------------------------------------------------------
            | Vehicle & Client
            |------------------------------------------------------------------
            */

            Section::make(__('messages.vehicle_and_client'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('client.display_name')
                            ->label(__('messages.client'))
                            ->placeholder('-'),
                        TextEntry::make('vehicle_display')
                            ->label(__('messages.vehicle'))
                            ->getStateUsing(fn (RepairTicket $record) => $record->vehicle_display)
                            ->placeholder('-'),
                        TextEntry::make('mileage')
                            ->label(__('messages.mileage_at_reception'))
                            ->numeric()
                            ->suffix(' km'),
                    ]),
                    Grid::make(3)->schema([
                        IconEntry::make('is_foreign_vehicle')
                            ->label(__('messages.foreign_vehicle'))
                            ->boolean(),
                        TextEntry::make('sale.sale_number')
                            ->label(__('messages.linked_sale'))
                            ->placeholder('-')
                            ->color('primary')
                            ->url(fn (RepairTicket $record) => $record->sale_id
                                ? SaleResource::getUrl('view', ['record' => $record->sale_id])
                                : null),
                        TextEntry::make('technician.name')
                            ->label(__('messages.lead_technician'))
                            ->placeholder('-'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('foreign_brand')
                            ->label(__('messages.brand'))
                            ->placeholder('-')
                            ->visible(fn (RepairTicket $record) => $record->is_foreign_vehicle),
                        TextEntry::make('foreign_model')
                            ->label(__('messages.model'))
                            ->placeholder('-')
                            ->visible(fn (RepairTicket $record) => $record->is_foreign_vehicle),
                        TextEntry::make('foreign_chassis')
                            ->label(__('messages.chassis_number'))
                            ->placeholder('-')
                            ->visible(fn (RepairTicket $record) => $record->is_foreign_vehicle),
                    ]),
                ]),

            /*
            |------------------------------------------------------------------
            | Diagnostic
            |------------------------------------------------------------------
            */

            Section::make(__('messages.diagnostic'))
                ->schema([
                    TextEntry::make('problem_description')
                        ->label(__('messages.problem_description'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('diagnostic')
                        ->label(__('messages.diagnostic_notes'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('before_state')
                        ->label(__('messages.vehicle_state_before'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        IconEntry::make('is_warranty')
                            ->label(__('messages.is_warranty'))
                            ->boolean(),
                        TextEntry::make('warranty_status')
                            ->label(__('messages.warranty_status'))
                            ->placeholder('-'),
                    ]),
                ]),

            /*
            |------------------------------------------------------------------
            | Financials
            |------------------------------------------------------------------
            */

            Section::make(__('messages.financials'))
                ->schema([
                    Grid::make(5)->schema([
                        TextEntry::make('labor_cost')
                            ->label(__('messages.labor_cost'))
                            ->money('MAD'),
                        TextEntry::make('parts_cost')
                            ->label(__('messages.parts_cost'))
                            ->money('MAD'),
                        TextEntry::make('discount_amount')
                            ->label(__('messages.discount_amount'))
                            ->money('MAD'),
                        TextEntry::make('total_cost')
                            ->label(__('messages.total_cost'))
                            ->money('MAD')
                            ->weight('bold'),
                        TextEntry::make('remaining_amount')
                            ->label(__('messages.remaining_amount'))
                            ->money('MAD')
                            ->color(fn (RepairTicket $record) => (float) $record->remaining_amount > 0 ? 'danger' : 'success')
                            ->weight('bold'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('payment_status')
                            ->label(__('messages.payment_status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid'    => 'success',
                                'partial' => 'warning',
                                default   => 'danger',
                            })
                            ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'unpaid'))),
                        IconEntry::make('discount_validated')
                            ->label(__('messages.discount_validated'))
                            ->boolean(),
                        TextEntry::make('discountValidator.name')
                            ->label(__('messages.validated_by'))
                            ->placeholder('-'),
                    ]),
                    TextEntry::make('discount_note')
                        ->label(__('messages.discount_note'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),

            /*
            |------------------------------------------------------------------
            | Payment History
            |------------------------------------------------------------------
            */

            Section::make(__('messages.payments'))
                ->schema([
                    RepeatableEntry::make('payments')
                        ->label('')
                        ->schema([
                            Grid::make(5)->schema([
                                TextEntry::make('created_at')
                                    ->label(__('messages.date'))
                                    ->date(),
                                TextEntry::make('amount')
                                    ->label(__('messages.amount'))
                                    ->money('MAD')
                                    ->weight('bold'),
                                TextEntry::make('payment_method')
                                    ->label(__('messages.payment_method'))
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'cash'          => __('messages.cash'),
                                        'card'          => __('messages.card'),
                                        'cheque'        => __('messages.cheque'),
                                        'bank_transfer' => __('messages.bank_transfer'),
                                        default         => $state,
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'cash'          => 'success',
                                        'card'          => 'info',
                                        'cheque'        => 'warning',
                                        'bank_transfer' => 'primary',
                                        default         => 'gray',
                                    }),
                                TextEntry::make('status')
                                    ->label(__('messages.status'))
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'paid'               => 'success',
                                        'pending_validation' => 'warning',
                                        'pending'            => 'warning',
                                        'bounced'            => 'danger',
                                        'cancelled'          => 'danger',
                                        'rejected'           => 'danger',
                                        default              => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'pending'))),
                                TextEntry::make('reference')
                                    ->label(__('messages.reference'))
                                    ->placeholder('-'),
                            ]),
                        ])
                        ->columnSpanFull(),
                ])
                ->visible(fn (RepairTicket $record) => $record->payments()->exists()),

            /*
            |------------------------------------------------------------------
            | Status & Payment
            |------------------------------------------------------------------
            */

            Section::make(__('messages.status_and_notes'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('payment_status')
                            ->label(__('messages.payment_status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid'    => 'success',
                                'partial' => 'warning',
                                default   => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'unpaid'))),
                        TextEntry::make('opened_at')
                            ->label(__('messages.opened_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('completed_at')
                            ->label(__('messages.completed_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
                    TextEntry::make('technician_notes')
                        ->label(__('messages.technician_notes'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('after_state')
                        ->label(__('messages.vehicle_state_after'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),

        ]);
    }
}
