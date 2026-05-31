<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;

use App\Models\Transaction;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Forms;

use Filament\Resources\Resource;

use Filament\Actions\DeleteAction;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model =
        Transaction::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-banknotes';

    protected static ?int $navigationSort =
        2;

    protected static ?string $recordTitleAttribute =
        'type';

    public static function getNavigationLabel(): string
    {
        return __('messages.transactions');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return __('messages.accounting');
    }

    public static function getModelLabel(): string
    {
        return __('messages.transaction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.transactions');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_transactions'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_transactions'
        ) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('Super Admin')
            || auth()->user()?->hasRole('Admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('Super Admin')
            || auth()->user()?->hasRole('Admin');
    }

    public static function form(
        Schema $schema
    ): Schema {

        return $schema

            ->components([

                Section::make(
                    __('messages.general_information')
                )

                    ->schema([

                        Forms\Components\Select::make('type')
                            ->label(__('messages.type'))
                            ->options([
                                'payment'       => __('messages.payment'),
                                'reimbursement' => __('messages.reimbursement'),
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'payment') {
                                    $set('direction', 'in');
                                } elseif ($state === 'reimbursement') {
                                    $set('direction', 'out');
                                }
                            }),

                        Forms\Components\Select::make('sale_id')
                            ->label(__('messages.sale'))
                            ->options(fn () =>
                                \App\Models\Sale::withoutGlobalScopes()
                                    ->where('company_id', session('company_id'))
                                    ->whereIn('payment_status', ['unpaid', 'partial'])
                                    ->with('client')
                                    ->get()
                                    ->mapWithKeys(fn ($sale) => [
                                        $sale->id => $sale->sale_number
                                            . ' – '
                                            . ($sale->client?->name ?? __('messages.no_client'))
                                            . ' (' . number_format((float) $sale->remaining_amount, 2) . ' MAD)',
                                    ])
                            )
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $sale = \App\Models\Sale::withoutGlobalScopes()->find($state);
                                    if ($sale) {
                                        $set('amount', $sale->remaining_amount);
                                    }
                                }
                            })
                            ->visible(fn ($get) => $get('type') === 'payment')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('messages.amount'))
                            ->numeric()
                            ->minValue(0.01)
                            ->suffix('MAD')
                            ->required(),

                        Forms\Components\Select::make('direction')
                            ->label(__('messages.direction'))
                            ->options([
                                'in'  => __('messages.in'),
                                'out' => __('messages.out'),
                            ])
                            ->default('in')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->hidden(fn ($get) => $get('type') === 'payment'),

                        Forms\Components\Select::make('payment_method')
                            ->label(__('messages.payment_method'))
                            ->options([
                                'cash'          => __('messages.cash'),
                                'card'          => __('messages.card'),
                                'bank_transfer' => __('messages.bank_transfer'),
                                'cheque'        => __('messages.cheque'),
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('status', in_array($state, ['cash', 'card']) ? 'validated' : 'pending');
                            })
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label(__('messages.status'))
                            ->options([
                                'pending'   => __('messages.pending'),
                                'validated' => __('messages.validated'),
                                'cancelled' => __('messages.cancelled'),
                            ])
                            ->default('validated')
                            ->required(),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->label(__('messages.transaction_date'))
                            ->default(now())
                            ->required(),

                    ])

                    ->columns(2),

                Section::make(
                    __('messages.notes')
                )

                    ->schema([

                        Forms\Components\Textarea::make(
                            'notes'
                        )

                            ->label(
                                __('messages.notes')
                            ),

                    ]),

            ]);
    }

    public static function table(
        Table $table
    ): Table {

        return $table

            ->columns([

                Tables\Columns\TextColumn::make(
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->badge()

                    ->searchable()

                    ->sortable()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'sale' =>
                                __('messages.sale'),

                            'payment' =>
                                __('messages.payment'),

                            'repair' =>
                                __('messages.repair'),

                            'expense' =>
                                __('messages.expense'),

                            'refund' =>
                                __('messages.refund'),

                            'credit' =>
                                __('messages.credit'),

                            'reimbursement' =>
                                __('messages.reimbursement'),

                            default => $state,

                        }
                    ),

                Tables\Columns\TextColumn::make(
                    'amount'
                )

                    ->label(
                        __('messages.amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'pending' =>
                                __('messages.pending'),

                            'validated' =>
                                __('messages.validated'),

                            'cancelled' =>
                                __('messages.cancelled'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'pending' => 'warning',

                        'validated' => 'success',

                        'cancelled' => 'danger',

                        default => 'gray',

                    }),

                Tables\Columns\TextColumn::make(
                    'transaction_date'
                )

                    ->label(
                        __('messages.transaction_date')
                    )

                    ->date()

                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'created_at'
                )

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable(),

            ])

            ->filters([

                Tables\Filters\SelectFilter::make(
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->options([

                        'sale' =>
                            __('messages.sale'),

                        'payment' =>
                            __('messages.payment'),

                        'repair' =>
                            __('messages.repair'),

                        'expense' =>
                            __('messages.expense'),

                        'refund' =>
                            __('messages.refund'),

                        'credit' =>
                            __('messages.credit'),

                        'reimbursement' =>
                            __('messages.reimbursement'),

                    ]),

                Tables\Filters\SelectFilter::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->options([

                        'pending' =>
                            __('messages.pending'),

                        'validated' =>
                            __('messages.validated'),

                        'cancelled' =>
                            __('messages.cancelled'),

                    ]),

            ])

            ->recordUrl(

                fn ($record) => static::getUrl(
                    'edit',
                    ['record' => $record]
                )

            )

            ->actions([

                DeleteAction::make(),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                ]),

            ]);
    }

    public static function getPages(): array
    {
        return [

            'index' =>
                ListTransactions::route('/'),

            'create' =>
                CreateTransaction::route('/create'),

            'view' =>
                ViewTransaction::route('/{record}'),

            'edit' =>
                EditTransaction::route('/{record}/edit'),

        ];
    }
}