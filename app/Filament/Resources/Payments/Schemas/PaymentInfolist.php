<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Payment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('messages.payment_information'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('amount')
                            ->label(__('messages.amount'))
                            ->money('MAD'),
                        TextEntry::make('payment_method')
                            ->label(__('messages.payment_method'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('messages.' . $state)),
                        TextEntry::make('status')
                            ->label(__('messages.status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid'                => 'success',
                                'received'            => 'info',
                                'pending_validation'  => 'warning',
                                'pending'             => 'warning',
                                'bounced'             => 'danger',
                                'cancelled', 'rejected' => 'danger',
                                default               => 'gray',
                            }),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('reference')
                            ->label(__('messages.reference'))
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label(__('messages.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                ]),

            Section::make(__('messages.linked_records'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('sale.sale_number')
                            ->label(__('messages.sale'))
                            ->placeholder('-')
                            ->color('primary')
                            ->url(fn (Payment $record) => $record->sale_id
                                ? \App\Filament\Resources\Sales\SaleResource::getUrl('view', ['record' => $record->sale_id])
                                : null),
                        TextEntry::make('client.display_name')
                            ->label(__('messages.client'))
                            ->placeholder('-'),
                        TextEntry::make('repairTicket.ticket_number')
                            ->label(__('messages.repair_ticket'))
                            ->placeholder('-'),
                    ]),
                ]),

            Section::make(__('messages.cheque_information'))
                ->visible(fn (Payment $record) => $record->payment_method === 'cheque')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('chequePayment.cheque_number')
                            ->label(__('messages.cheque_number'))
                            ->placeholder('-'),
                        TextEntry::make('chequePayment.bank_name')
                            ->label(__('messages.bank_name'))
                            ->placeholder('-'),
                        TextEntry::make('chequePayment.due_date')
                            ->label(__('messages.due_date'))
                            ->date()
                            ->placeholder('-'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('chequePayment.status')
                            ->label(__('messages.cheque_status'))
                            ->badge()
                            ->placeholder('-'),
                    ]),
                ]),

            Section::make(__('messages.bank_transfer_information'))
                ->visible(fn (Payment $record) => $record->payment_method === 'bank_transfer')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('bankTransferPayment.bank_name')
                            ->label(__('messages.bank_name'))
                            ->placeholder('-'),
                        TextEntry::make('bankTransferPayment.reference_number')
                            ->label(__('messages.reference_number'))
                            ->placeholder('-'),
                        TextEntry::make('bankTransferPayment.transfer_date')
                            ->label(__('messages.transfer_date'))
                            ->date()
                            ->placeholder('-'),
                    ]),
                ]),

        ]);
    }
}
