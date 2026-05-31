<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Client;
use App\Models\Payment;
use App\Models\RepairTicket;
use App\Models\Sale;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /*
            |--------------------------------------------------------------------------
            | Core Payment Info
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.payment_information'))
                ->schema([

                    // Link to Sale — hidden when a repair ticket is already selected
                    Forms\Components\Select::make('sale_id')
                        ->label(__('messages.sale'))
                        ->options(
                            Sale::withoutGlobalScopes()
                                ->orderByDesc('id')
                                ->limit(200)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => ($s->sale_number ?? '#' . $s->id) . ' — ' . number_format((float) $s->remaining_amount, 2) . ' MAD',
                                ])
                        )
                        ->searchable()
                        ->live()
                        ->hidden(fn (callable $get) => filled($get('repair_ticket_id')))
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) {
                                return;
                            }

                            $sale = Sale::withoutGlobalScopes()->find($state);
                            if (! $sale) {
                                return;
                            }

                            $set('amount', max(0, (float) $sale->remaining_amount));
                            $set('client_id', $sale->client_id);
                            $set('repair_ticket_id', null);
                            $set('status', match ($get('payment_method')) {
                                'cash', 'card'  => 'paid',
                                'cheque'        => 'received',
                                'bank_transfer' => 'sent',
                                default         => 'pending',
                            });

                            // Import the most recent cheque payment details from this sale.
                            $cheque = Payment::withoutGlobalScopes()
                                ->where('sale_id', $state)
                                ->where('payment_method', 'cheque')
                                ->latest()
                                ->first()?->chequePayment;

                            if ($cheque) {
                                $set('chequePayment.cheque_number', $cheque->cheque_number);
                                $set('chequePayment.bank_name', $cheque->bank_name);
                                $set('chequePayment.due_date', $cheque->due_date?->format('Y-m-d'));
                            }
                        })
                        ->nullable(),

                    // Link to Repair Ticket — hidden when a sale is already selected
                    Forms\Components\Select::make('repair_ticket_id')
                        ->label(__('messages.repair_ticket'))
                        ->options(
                            RepairTicket::withoutGlobalScopes()
                                ->orderByDesc('id')
                                ->limit(200)
                                ->get()
                                ->mapWithKeys(fn ($t) => [
                                    $t->id => ($t->ticket_number ?? '#' . $t->id) . ' — ' . number_format((float) $t->total_cost, 2) . ' MAD',
                                ])
                        )
                        ->searchable()
                        ->live()
                        ->hidden(fn (callable $get) => filled($get('sale_id')))
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $ticket = RepairTicket::withoutGlobalScopes()->find($state);
                                if ($ticket) {
                                    $set('amount', max(0, (float) $ticket->total_cost));
                                    $set('client_id', $ticket->client_id);
                                    $set('sale_id', null);
                                }
                            }
                        })
                        ->nullable(),

                    // Client (auto-filled, editable)
                    Forms\Components\Select::make('client_id')
                        ->label(__('messages.client'))
                        ->options(fn () => Client::query()
                            ->where('is_blocked', false)
                            ->where('is_active', true)
                            ->get()
                            ->pluck('display_name', 'id')
                            ->filter(fn ($v) => filled($v))
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->createOptionForm([
                            Forms\Components\Select::make('client_type')
                                ->label(__('messages.client_type'))
                                ->options([
                                    'person'         => __('messages.person'),
                                    'company'        => __('messages.company'),
                                    'administration' => __('messages.administration'),
                                ])
                                ->default('person')
                                ->live()
                                ->required(),
                            Forms\Components\TextInput::make('first_name')
                                ->label(__('messages.first_name'))
                                ->visible(fn ($get) => ($get('client_type') ?? 'person') === 'person')
                                ->required(fn ($get) => ($get('client_type') ?? 'person') === 'person'),
                            Forms\Components\TextInput::make('last_name')
                                ->label(__('messages.last_name'))
                                ->visible(fn ($get) => ($get('client_type') ?? 'person') === 'person'),
                            Forms\Components\TextInput::make('company_name')
                                ->label(__('messages.company_name'))
                                ->visible(fn ($get) => $get('client_type') === 'company')
                                ->required(fn ($get) => $get('client_type') === 'company'),
                            Forms\Components\TextInput::make('administration_name')
                                ->label(__('messages.administration_name'))
                                ->visible(fn ($get) => $get('client_type') === 'administration')
                                ->required(fn ($get) => $get('client_type') === 'administration'),
                            Forms\Components\TextInput::make('phone')->label(__('messages.phone'))->tel(),
                            Forms\Components\TextInput::make('email')->label(__('messages.email'))->email(),
                        ])
                        ->createOptionUsing(fn (array $data) => Client::create(array_merge(['is_active' => true, 'is_blocked' => false], $data))->id),

                    // Amount (auto-filled from sale/repair)
                    Forms\Components\TextInput::make('amount')
                        ->label(__('messages.amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->suffix('MAD')
                        ->required(),

                    // Payment method — drives status options and sub-sections
                    Forms\Components\Select::make('payment_method')
                        ->label(__('messages.payment_method'))
                        ->options([
                            'cash'          => __('messages.cash'),
                            'card'          => __('messages.card'),
                            'cheque'        => __('messages.cheque'),
                            'bank_transfer' => __('messages.bank_transfer'),
                        ])
                        ->default('cash')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('status', match ($state) {
                                'cash', 'card'  => 'paid',
                                'cheque'        => 'received',
                                'bank_transfer' => 'sent',
                                default         => 'pending',
                            });
                        })
                        ->required(),

                    // Payment status — options depend on method
                    Forms\Components\Select::make('status')
                        ->label(__('messages.status'))
                        ->options(function (callable $get) {
                            return match ($get('payment_method')) {
                                'cheque' => [
                                    'received' => __('messages.cheque_received'),
                                    'paid'     => __('messages.cheque_paid'),
                                    'bounced'  => __('messages.cheque_bounced'),
                                ],
                                'bank_transfer' => [
                                    'sent'     => __('messages.transfer_sent'),
                                    'received' => __('messages.transfer_received'),
                                ],
                                default => [
                                    'paid'      => __('messages.paid'),
                                    'pending'   => __('messages.pending'),
                                    'cancelled' => __('messages.cancelled'),
                                ],
                            };
                        })
                        ->default('paid')
                        ->live()
                        ->required(),

                    Forms\Components\TextInput::make('reference')
                        ->label(__('messages.reference'))
                        ->visible(fn (callable $get) => ! in_array($get('payment_method'), ['cash', 'cheque'], true))
                        ->required(fn (callable $get) => $get('payment_method') === 'bank_transfer')
                        ->maxLength(100),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('messages.notes'))
                        ->rows(2)
                        ->columnSpanFull(),

                ])
                ->columns(2),

            /*
            |--------------------------------------------------------------------------
            | Cheque Details
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.cheque_information'))
                ->visible(fn (callable $get) => $get('payment_method') === 'cheque')
                ->schema([

                    Forms\Components\TextInput::make('chequePayment.cheque_number')
                        ->label(__('messages.cheque_number'))
                        ->required(),

                    Forms\Components\Select::make('chequePayment.bank_name')
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
                            'Saham Bank'                      => 'Saham Bank',
                            'Umnia Bank'                      => 'Umnia Bank',
                            'Bank Assafa'                     => 'Bank Assafa',
                            'Bank Al Yousr'                   => 'Bank Al Yousr',
                            'Al Akhdar Bank'                  => 'Al Akhdar Bank',
                            'Bank Al-Tamweel wal-Inma'        => 'Bank Al-Tamweel wal-Inma',
                        ])
                        ->searchable()
                        ->required(),

                    Forms\Components\DatePicker::make('chequePayment.due_date')
                        ->label(__('messages.due_date'))
                        ->required(),

                    Forms\Components\Select::make('chequePayment.status')
                        ->label(__('messages.cheque_status'))
                        ->options([
                            'received' => __('messages.cheque_received'),
                            'paid'     => __('messages.cheque_paid'),
                            'bounced'  => __('messages.cheque_bounced'),
                        ])
                        ->default('received'),

                    Forms\Components\FileUpload::make('chequePayment.scan_path')
                        ->label(__('messages.scan'))
                        ->directory('cheques')
                        ->columnSpanFull(),

                ])
                ->columns(2),

            /*
            |--------------------------------------------------------------------------
            | Bank Transfer Details
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.bank_transfer_information'))
                ->visible(fn (callable $get) => $get('payment_method') === 'bank_transfer')
                ->schema([

                    Forms\Components\TextInput::make('bankTransferPayment.bank_name')
                        ->label(__('messages.bank_name'))
                        ->required(),

                    Forms\Components\TextInput::make('bankTransferPayment.reference_number')
                        ->label(__('messages.reference_number'))
                        ->required(),

                    Forms\Components\DatePicker::make('bankTransferPayment.transfer_date')
                        ->label(__('messages.transfer_date'))
                        ->required(),

                    Forms\Components\Select::make('bankTransferPayment.status')
                        ->label(__('messages.transfer_status'))
                        ->options([
                            'sent'     => __('messages.transfer_sent'),
                            'received' => __('messages.transfer_received'),
                        ])
                        ->default('sent'),

                    Forms\Components\FileUpload::make('bankTransferPayment.confirmation_file')
                        ->label(__('messages.confirmation_file'))
                        ->directory('bank_transfers')
                        ->columnSpanFull(),

                ])
                ->columns(2),

        ]);
    }
}
