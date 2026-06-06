<?php

namespace App\Filament\Resources\ClientBalances;

use App\Filament\Resources\ClientBalances\Pages\ListClientBalances;
use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Accounting → Client Balances.
 *
 * A read-only, accounting-grade view of every client's financial position.
 * Built on the Client model but registered as a separate resource so it can
 * live under the Accounting group with its own columns, filters and widgets.
 *
 * SOURCE OF TRUTH: every money figure is eager-loaded via
 * Client::scopeWithAccountingAggregates(), whose outstanding-balance formula is
 * byte-for-byte identical to the `outstanding_balance` accessor used by the
 * client detail and list pages. No stale `clients.balance` column is read.
 */
class ClientBalanceResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'display_name';

    // ── Identity / navigation ────────────────────────────────────────────────

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('messages.accounting');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.client_balances');
    }

    public static function getModelLabel(): string
    {
        return __('messages.client_balance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.client_balances');
    }

    // ── Permissions (accounting staff) ───────────────────────────────────────

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_payments') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_payments') ?? false;
    }

    public static function canCreate(): bool
    {
        return false; // read-only accounting view
    }

    // ── Search ───────────────────────────────────────────────────────────────

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'company_name', 'administration_name',
            'ice', 'cin', 'phone', 'email', 'representative_name'];
    }

    // ── Header widgets ───────────────────────────────────────────────────────

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\ClientBalances\Widgets\ClientBalanceStatsWidget::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            // Single source of truth: all accounting figures from one scope.
            ->modifyQueryUsing(fn (Builder $query) => $query->withAccountingAggregates())
            ->defaultSort('outstanding_balance_sum', 'desc')
            ->columns([

                Tables\Columns\TextColumn::make('display_name')
                    ->label(__('messages.client'))
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->where(fn (Builder $w) => $w
                            ->where('first_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%")
                            ->orWhere('company_name', 'like', "%{$s}%")
                            ->orWhere('administration_name', 'like', "%{$s}%")
                            ->orWhere('ice', 'like', "%{$s}%")
                            ->orWhere('cin', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")
                            ->orWhere('representative_name', 'like', "%{$s}%")))
                    ->weight('bold')
                    ->description(fn (Client $r) => $r->phone)
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_type')
                    ->label(__('messages.client_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'person'         => __('messages.person'),
                        'company'        => __('messages.company'),
                        'administration' => __('messages.administration'),
                        default          => $state,
                    })
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->state(fn (Client $r) => $r->is_blocked
                        ? __('messages.blocked')
                        : ($r->is_active ? __('messages.active') : __('messages.inactive')))
                    ->color(fn (Client $r) => $r->is_blocked
                        ? 'danger'
                        : ($r->is_active ? 'success' : 'gray')),

                Tables\Columns\TextColumn::make('last_payment_at')
                    ->label(__('messages.last_payment_date'))
                    ->dateTime('d/m/Y')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_sale_at')
                    ->label(__('messages.last_sale_date'))
                    ->dateTime('d/m/Y')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_sales')
                    ->label(__('messages.total_sales'))
                    ->money('MAD')
                    ->sortable(query: fn (Builder $q, string $d) => $q->orderBy('total_sales_sum', $d))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_payments')
                    ->label(__('messages.total_payments'))
                    ->money('MAD')
                    ->sortable(query: fn (Builder $q, string $d) => $q->orderBy('total_payments_sum', $d))
                    ->toggleable(),

                // THE source-of-truth column — same accessor as detail/list pages.
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label(__('messages.outstanding_balance'))
                    ->money('MAD')
                    ->badge()
                    ->color(fn ($state) => self::balanceColor((float) $state))
                    ->sortable(query: fn (Builder $q, string $d) => $q->orderBy('outstanding_balance_sum', $d)),

                Tables\Columns\TextColumn::make('credit_balance')
                    ->label(__('messages.credit_balance'))
                    ->money('MAD')
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'info' : 'gray')
                    ->sortable(query: fn (Builder $q, string $d) => $q->orderBy('credit_balance_sum', $d))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('open_sales_count')
                    ->label(__('messages.open_sales'))
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('overdue_sales_count')
                    ->label(__('messages.overdue_sales'))
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'danger' : 'gray')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('messages.company'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('only_debtors')
                    ->label(__('messages.only_debtors'))
                    ->query(fn (Builder $q) => $q->whereHas('sales', fn (Builder $s) => $s
                        ->whereIn('payment_status', ['unpaid', 'partial']))),

                Tables\Filters\Filter::make('only_credit')
                    ->label(__('messages.only_credit_clients'))
                    ->query(fn (Builder $q) => $q->whereHas('sales', fn (Builder $s) => $s
                        ->whereColumn('paid_amount', '>', 'total'))),

                Tables\Filters\Filter::make('only_overdue')
                    ->label(__('messages.only_overdue'))
                    ->query(fn (Builder $q) => $q->whereHas('sales', fn (Builder $s) => $s
                        ->whereIn('payment_status', ['unpaid', 'partial'])
                        ->whereDate('sale_date', '<', now()->subDays(Client::OVERDUE_DAYS)))),

                Tables\Filters\Filter::make('only_active')
                    ->label(__('messages.only_active'))
                    ->query(fn (Builder $q) => $q->where('is_active', true)),

                Tables\Filters\Filter::make('only_blocked')
                    ->label(__('messages.only_blocked'))
                    ->query(fn (Builder $q) => $q->where('is_blocked', true)),

                Tables\Filters\SelectFilter::make('client_type')
                    ->label(__('messages.client_type'))
                    ->options([
                        'person'         => __('messages.person'),
                        'company'        => __('messages.company'),
                        'administration' => __('messages.administration'),
                    ]),

                Tables\Filters\Filter::make('balance_range')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('min')
                            ->label(__('messages.min'))->numeric(),
                        \Filament\Forms\Components\TextInput::make('max')
                            ->label(__('messages.max'))->numeric(),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['min'] ?? null, fn (Builder $q, $v) => $q
                            ->havingRaw('COALESCE(outstanding_balance_sum,0) >= ?', [$v]))
                        ->when($data['max'] ?? null, fn (Builder $q, $v) => $q
                            ->havingRaw('COALESCE(outstanding_balance_sum,0) <= ?', [$v]))),

                Tables\Filters\Filter::make('last_payment_date')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label(__('messages.from')),
                        \Filament\Forms\Components\DatePicker::make('until')->label(__('messages.until')),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q
                            ->whereHas('payments', fn (Builder $p) => $p->where('status', 'paid')->whereDate('created_at', '>=', $d)))
                        ->when($data['until'] ?? null, fn (Builder $q, $d) => $q
                            ->whereHas('payments', fn (Builder $p) => $p->where('status', 'paid')->whereDate('created_at', '<=', $d)))),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_client')
                        ->label(__('messages.view_client'))
                        ->icon('heroicon-o-eye')
                        ->url(fn (Client $r) => ClientResource::getUrl('view', ['record' => $r]))
                        ->openUrlInNewTab(),

                    Action::make('view_sales')
                        ->label(__('messages.view_sales'))
                        ->icon('heroicon-o-shopping-cart')
                        ->url(fn (Client $r) => ClientResource::getUrl('view', ['record' => $r]).'#relation-manager')
                        ->openUrlInNewTab(),

                    Action::make('record_payment')
                        ->label(__('messages.record_payment'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->url(fn (Client $r) => \App\Filament\Resources\Payments\PaymentResource::getUrl('create', ['client_id' => $r->id]))
                        ->visible(fn () => auth()->user()?->can('manage_payments') ?? false),

                    Action::make('account_statement')
                        ->label(__('messages.account_statement'))
                        ->icon('heroicon-o-document-chart-bar')
                        ->url(fn (Client $r) => route('clients.statement', $r))
                        ->openUrlInNewTab(),

                    Action::make('export_pdf')
                        ->label(__('messages.export_pdf'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Client $r) => route('clients.statement.pdf', $r))
                        ->openUrlInNewTab(),

                    Action::make('export_excel')
                        ->label(__('messages.export_excel'))
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (Client $r) => route('clients.statement.csv', $r))
                        ->openUrlInNewTab(),
                ]),
            ]);
    }

    /** Color rule shared by column + widgets: 0 green, small orange, high red. */
    public static function balanceColor(float $balance): string
    {
        return match (true) {
            $balance <= 0                              => 'success', // settled
            $balance <= 5000                           => 'warning', // small debt
            default                                    => 'danger',  // high debt
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientBalances::route('/'),
        ];
    }
}
