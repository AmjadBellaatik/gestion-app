<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Models\BankTransferPayment;
use App\Models\ChequePayment;
use App\Models\Payment;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    public static function getTitle(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): string {
        return __('messages.sales');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sale_number')
                    ->label(__('messages.sale_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total')
                    ->label(__('messages.total'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('messages.paid_amount'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label(__('messages.remaining_amount'))
                    ->money('MAD')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('payment_status')
                    ->label(__('messages.payment_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid'    => __('messages.paid'),
                        'partial' => __('messages.partial'),
                        'unpaid'  => __('messages.unpaid'),
                        default   => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'unpaid'  => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'completed' => __('messages.completed'),
                        'pending'   => __('messages.pending'),
                        'cancelled' => __('messages.cancelled'),
                        default     => $state,
                    }),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->date()
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->actions([

                Action::make('record_payment')
                    ->label(__('messages.record_payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->remaining_amount > 0)
                    ->fillForm(fn ($record) => [
                        'amount'         => $record->remaining_amount,
                        'payment_method' => 'cash',
                        'status'         => 'paid',
                    ])
                    ->form([

                        Section::make(__('messages.payment_information'))
                            ->schema([

                                TextInput::make('amount')
                                    ->label(__('messages.amount'))
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->suffix('MAD')
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

                                Select::make('status')
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

                                TextInput::make('reference')
                                    ->label(fn (callable $get) => $get('payment_method') === 'cheque'
                                        ? __('messages.cheque_number')
                                        : __('messages.reference'))
                                    ->visible(fn (callable $get) => $get('payment_method') !== 'cash' && $get('payment_method') !== 'card')
                                    ->required(fn (callable $get) => in_array($get('payment_method'), ['cheque', 'bank_transfer'], true))
                                    ->maxLength(100),

                                Textarea::make('notes')
                                    ->label(__('messages.notes'))
                                    ->rows(2)
                                    ->columnSpanFull(),

                            ])
                            ->columns(2),

                        Section::make(__('messages.cheque_information'))
                            ->visible(fn (callable $get) => $get('payment_method') === 'cheque')
                            ->schema([

                                TextInput::make('cheque_number')
                                    ->label(__('messages.cheque_number'))
                                    ->required(),

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
                                        'Saham Bank'                      => 'Saham Bank',
                                        'Umnia Bank'                      => 'Umnia Bank',
                                        'Bank Assafa'                     => 'Bank Assafa',
                                        'Bank Al Yousr'                   => 'Bank Al Yousr',
                                        'Al Akhdar Bank'                  => 'Al Akhdar Bank',
                                        'Bank Al-Tamweel wal-Inma'        => 'Bank Al-Tamweel wal-Inma',
                                    ])
                                    ->searchable()
                                    ->required(),

                                DatePicker::make('due_date')
                                    ->label(__('messages.due_date'))
                                    ->required(),

                                Select::make('cheque_status')
                                    ->label(__('messages.cheque_status'))
                                    ->options([
                                        'received' => __('messages.cheque_received'),
                                        'paid'     => __('messages.cheque_paid'),
                                        'bounced'  => __('messages.cheque_bounced'),
                                    ])
                                    ->default('received'),

                            ])
                            ->columns(2),

                        Section::make(__('messages.bank_transfer_information'))
                            ->visible(fn (callable $get) => $get('payment_method') === 'bank_transfer')
                            ->schema([

                                TextInput::make('bank_name')
                                    ->label(__('messages.bank_name'))
                                    ->required(),

                                TextInput::make('reference_number')
                                    ->label(__('messages.reference_number'))
                                    ->required(),

                                DatePicker::make('transfer_date')
                                    ->label(__('messages.transfer_date'))
                                    ->required(),

                                Select::make('transfer_status')
                                    ->label(__('messages.transfer_status'))
                                    ->options([
                                        'sent'     => __('messages.transfer_sent'),
                                        'received' => __('messages.transfer_received'),
                                    ])
                                    ->default('sent'),

                            ])
                            ->columns(2),

                    ])
                    ->action(function ($record, array $data): void {

                        $payment = Payment::create([
                            'sale_id'        => $record->id,
                            'client_id'      => $record->client_id,
                            'amount'         => $data['amount'],
                            'payment_method' => $data['payment_method'],
                            'status'         => $data['status'],
                            'reference'      => $data['reference'] ?? null,
                            'notes'          => $data['notes'] ?? null,
                        ]);

                        if ($data['payment_method'] === 'cheque') {
                            ChequePayment::create([
                                'payment_id'    => $payment->id,
                                'cheque_number' => $data['cheque_number'] ?? null,
                                'bank_name'     => $data['bank_name'] ?? null,
                                'due_date'      => $data['due_date'] ?? null,
                                'status'        => $data['cheque_status'] ?? 'received',
                            ]);
                        }

                        if ($data['payment_method'] === 'bank_transfer') {
                            BankTransferPayment::create([
                                'payment_id'       => $payment->id,
                                'bank_name'        => $data['bank_name'] ?? null,
                                'reference_number' => $data['reference_number'] ?? null,
                                'transfer_date'    => $data['transfer_date'] ?? null,
                                'status'           => $data['transfer_status'] ?? 'sent',
                            ]);
                        }

                        Notification::make()
                            ->title(__('messages.payment_recorded'))
                            ->success()
                            ->send();
                    }),

                Action::make('view')
                    ->label(__('messages.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.sales.view', $record)),

            ])
            ->recordUrl(null);
    }
}
