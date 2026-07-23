<?php

namespace App\Filament\Resources\ClientBalances\Widgets;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Reseller;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Accounting summary for the Client Balances page.
 *
 * Every figure derives from the SAME sales/payments tables (CompanyScope-aware)
 * as the table rows and the client detail page — no stale columns.
 */
class ClientBalanceStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Total client debt = SUM of remaining on unpaid/partial sales (source of truth).
        $totalDebt = (float) Sale::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('remaining_amount');

        // Total client credit = SUM of overpayments across sales.
        $totalCredit = (float) Sale::query()
            ->selectRaw('COALESCE(SUM(GREATEST(paid_amount - total, 0)), 0) AS c')
            ->value('c');

        // Distinct clients OR resellers in debt / credit — the balances table
        // now shows both in one list, so these counts cover both too.
        $clientsWithDebt = Client::query()
            ->whereHas('sales', fn ($q) => $q->whereIn('payment_status', ['unpaid', 'partial']))
            ->count()
            + Reseller::query()->where('current_debt', '>', 0)->count();

        $clientsWithCredit = Client::query()
            ->whereHas('sales', fn ($q) => $q->whereColumn('paid_amount', '>', 'total'))
            ->count()
            + Reseller::query()->where('credit_balance', '>', 0)->count();

        // Overdue amount = remaining on unpaid/partial sales aged past threshold.
        $overdue = (float) Sale::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereDate('sale_date', '<', now()->subDays(Client::OVERDUE_DAYS))
            ->sum('remaining_amount');

        // Payments validated this calendar month.
        $paymentsThisMonth = (float) Payment::query()
            ->where('status', 'paid')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $fmt = fn (float $v) => number_format($v, 2, ',', ' ').' MAD';

        return [
            Stat::make(__('messages.total_client_debt'), $fmt($totalDebt))
                ->description(__('messages.outstanding_balance'))
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make(__('messages.total_client_credit'), $fmt($totalCredit))
                ->description(__('messages.credit_balance'))
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make(__('messages.clients_with_debt'), (string) $clientsWithDebt)
                ->color('warning')
                ->icon('heroicon-o-users'),

            Stat::make(__('messages.clients_with_credit'), (string) $clientsWithCredit)
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make(__('messages.overdue_amount'), $fmt($overdue))
                ->description(__('messages.overdue_sales'))
                ->color('danger')
                ->icon('heroicon-o-clock'),

            Stat::make(__('messages.payments_this_month'), $fmt($paymentsThisMonth))
                ->color('success')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
