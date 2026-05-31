<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make(__('messages.transaction_information'))
                    ->schema([

                        Select::make('type')
                            ->label(__('messages.type'))
                            ->options([
                                'sale_payment'   => __('messages.sale_payment'),
                                'repair_payment' => __('messages.repair_payment'),
                                'sale'           => __('messages.sale'),
                                'expense'        => __('messages.expense'),
                                'reimbursement'  => __('messages.reimbursement'),
                                'other'          => __('messages.other'),
                            ])
                            ->required()
                            ->live(),

                        Select::make('category')
                            ->label(__('messages.category'))
                            ->options([
                                'sale_payment'   => __('messages.sale_payment'),
                                'repair_payment' => __('messages.repair_payment'),
                                'other_payment'  => __('messages.other_payment'),
                                'expense'        => __('messages.expense'),
                                'reimbursement'  => __('messages.reimbursement'),
                            ])
                            ->nullable(),

                        TextInput::make('amount')
                            ->label(__('messages.amount'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix('MAD')
                            ->required(),

                        Select::make('direction')
                            ->label(__('messages.direction'))
                            ->options([
                                'income'  => __('messages.income'),
                                'expense' => __('messages.expense'),
                            ])
                            ->default('income')
                            ->required(),

                        Select::make('payment_method')
                            ->label(__('messages.payment_method'))
                            ->options([
                                'cash'          => __('messages.cash'),
                                'card'          => __('messages.card'),
                                'cheque'        => __('messages.cheque'),
                                'bank_transfer' => __('messages.bank_transfer'),
                            ])
                            ->nullable(),

                        Select::make('status')
                            ->label(__('messages.status'))
                            ->options([
                                'validated' => __('messages.validated'),
                                'pending'   => __('messages.pending'),
                                'cancelled' => __('messages.cancelled'),
                            ])
                            ->default('validated')
                            ->required(),

                        DatePicker::make('transaction_date')
                            ->label(__('messages.transaction_date'))
                            ->default(now())
                            ->required(),

                    ])
                    ->columns(2),

                Section::make(__('messages.notes'))
                    ->schema([
                        Textarea::make('description')
                            ->label(__('messages.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

            ]);
    }
}
