<?php

namespace App\Filament\Resources\ClientBalances;

use App\Filament\Resources\ClientBalances\Pages\ListClientBalances;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Resellers\ResellerResource;
use App\Models\ClientBalanceRow;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Accounting → Client Balances ("Soldes clients").
 *
 * A read-only, accounting-grade view of every client's AND reseller's
 * financial position, in one table — resellers are just another row here
 * (source_type = 'reseller'), not a separate column or page.
 *
 * SOURCE OF TRUTH: backed by the `client_balance_rows` SQL view (see its
 * migration), which computes the exact same outstanding-balance formula
 * Client::getOutstandingBalanceAttribute() uses for clients, and reads
 * Reseller::current_debt (kept in sync by Reseller::recalculate()) for
 * resellers.
 */
class ClientBalanceResource extends Resource
{
    protected static ?string $model = ClientBalanceRow::class;

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
        return ['display_name', 'ice', 'cin', 'phone', 'email', 'representative_name'];
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
            ->defaultSort('outstanding_balance_sum', 'desc')
            ->columns([

                Tables\Columns\TextColumn::make('display_name')
                    ->label(__('messages.client'))
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->where(fn (Builder $w) => $w
                            ->where('display_name', 'like', "%{$s}%")
                            ->orWhere('ice', 'like', "%{$s}%")
                            ->orWhere('cin', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")
                            ->orWhere('representative_name', 'like', "%{$s}%")))
                    ->weight('bold')
                    ->description(fn (ClientBalanceRow $r) => $r->phone)
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_type')
                    ->label(__('messages.client_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'person'         => __('messages.person'),
                        'company'        => __('messages.company'),
                        'administration' => __('messages.administration'),
                        'reseller'       => __('messages.reseller'),
                        default          => $state,
                    })
                    ->color(fn (string $state) => $state === 'reseller' ? 'info' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->state(fn (ClientBalanceRow $r) => $r->is_blocked
                        ? __('messages.blocked')
                        : ($r->is_active ? __('messages.active') : __('messages.inactive')))
                    ->color(fn (ClientBalanceRow $r) => $r->is_blocked
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

                Tables\Columns\TextColumn::make('total_sales_sum')
                    ->label(__('messages.total_sales'))
                    ->money('MAD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_payments_sum')
                    ->label(__('messages.total_payments'))
                    ->money('MAD')
                    ->sortable()
                    ->toggleable(),

                // THE source-of-truth column — clients: live sales aggregate;
                // resellers: Reseller::current_debt. Same column either way.
                Tables\Columns\TextColumn::make('outstanding_balance_sum')
                    ->label(__('messages.outstanding_balance'))
                    ->money('MAD')
                    ->badge()
                    ->color(fn ($state) => self::balanceColor((float) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_balance_sum')
                    ->label(__('messages.credit_balance'))
                    ->money('MAD')
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'info' : 'gray')
                    ->sortable()
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('only_debtors')
                    ->label(__('messages.only_debtors'))
                    ->query(fn (Builder $q) => $q->where('outstanding_balance_sum', '>', 0)),

                Tables\Filters\Filter::make('only_credit')
                    ->label(__('messages.only_credit_clients'))
                    ->query(fn (Builder $q) => $q->where('credit_balance_sum', '>', 0)),

                Tables\Filters\Filter::make('only_overdue')
                    ->label(__('messages.only_overdue'))
                    ->query(fn (Builder $q) => $q->where('overdue_sales_count', '>', 0)),

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
                        'reseller'       => __('messages.reseller'),
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
                            ->where('outstanding_balance_sum', '>=', $v))
                        ->when($data['max'] ?? null, fn (Builder $q, $v) => $q
                            ->where('outstanding_balance_sum', '<=', $v))),

                Tables\Filters\Filter::make('last_payment_date')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label(__('messages.from')),
                        \Filament\Forms\Components\DatePicker::make('until')->label(__('messages.until')),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q
                            ->whereDate('last_payment_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d) => $q
                            ->whereDate('last_payment_at', '<=', $d))),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_client')
                        ->label(fn (ClientBalanceRow $r) => $r->isReseller() ? __('messages.view_reseller') : __('messages.view_client'))
                        ->icon('heroicon-o-eye')
                        ->url(fn (ClientBalanceRow $r) => $r->isReseller()
                            ? ResellerResource::getUrl('view', ['record' => $r->source_id])
                            : ClientResource::getUrl('view', ['record' => $r->source_id]))
                        ->openUrlInNewTab(),

                    Action::make('view_sales')
                        ->label(__('messages.view_sales'))
                        ->icon('heroicon-o-shopping-cart')
                        ->url(fn (ClientBalanceRow $r) => ($r->isReseller()
                            ? ResellerResource::getUrl('view', ['record' => $r->source_id])
                            : ClientResource::getUrl('view', ['record' => $r->source_id])) . '#relation-manager')
                        ->openUrlInNewTab(),

                    Action::make('record_payment')
                        ->label(__('messages.record_payment'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->url(fn (ClientBalanceRow $r) => PaymentResource::getUrl('create', ['client_id' => $r->source_id]))
                        // Payments have no direct reseller_id column (only via
                        // sale.reseller), so the quick-create shortcut only
                        // applies to client rows — record it from the sale itself
                        // for resellers.
                        ->visible(fn (ClientBalanceRow $r) => ! $r->isReseller()
                            && (auth()->user()?->can('manage_payments') ?? false)),

                    Action::make('account_statement')
                        ->label(__('messages.account_statement'))
                        ->icon('heroicon-o-document-chart-bar')
                        ->url(fn (ClientBalanceRow $r) => route('clients.statement', $r->source_id))
                        ->visible(fn (ClientBalanceRow $r) => ! $r->isReseller())
                        ->openUrlInNewTab(),

                    Action::make('export_pdf')
                        ->label(__('messages.export_pdf'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (ClientBalanceRow $r) => route('clients.statement.pdf', $r->source_id))
                        ->visible(fn (ClientBalanceRow $r) => ! $r->isReseller())
                        ->openUrlInNewTab(),

                    Action::make('export_excel')
                        ->label(__('messages.export_excel'))
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (ClientBalanceRow $r) => route('clients.statement.csv', $r->source_id))
                        ->visible(fn (ClientBalanceRow $r) => ! $r->isReseller())
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
