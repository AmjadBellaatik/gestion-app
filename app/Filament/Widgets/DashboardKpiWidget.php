<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\RepairTicket;
use App\Models\Sale;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardKpiWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $year  = now()->year;
        $month = now()->month;
        $prevMonth = now()->subMonth()->month;
        $prevYear  = now()->subMonth()->year;

        // ── Revenue ──────────────────────────────────────────────────────────
        $revenueCurrent = Sale::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('total');

        $revenuePrev = Sale::whereYear('created_at', $prevYear)
            ->whereMonth('created_at', $prevMonth)
            ->sum('total');

        $revenueChart = $this->monthlyTotals(
            fn ($m, $y) => Sale::whereYear('created_at', $y)->whereMonth('created_at', $m)->sum('total')
        );

        // ── Expenses ─────────────────────────────────────────────────────────
        $expenseCurrent = Expense::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');

        $expensePrev = Expense::whereYear('created_at', $prevYear)
            ->whereMonth('created_at', $prevMonth)
            ->sum('amount');

        $expenseChart = $this->monthlyTotals(
            fn ($m, $y) => Expense::whereYear('created_at', $y)->whereMonth('created_at', $m)->sum('amount')
        );

        // ── Net Profit ────────────────────────────────────────────────────────
        $profitCurrent = $revenueCurrent - $expenseCurrent;
        $profitPrev    = $revenuePrev - $expensePrev;
        $profitChart   = array_map(fn ($r, $e) => $r - $e, $revenueChart, $expenseChart);

        // ── Active Repair Tickets ─────────────────────────────────────────────
        $activeTickets = RepairTicket::whereIn('status', ['open', 'diagnostic', 'waiting_approval', 'approved', 'waiting_parts', 'in_progress'])->count();
        $activeChart   = $this->monthlyTotals(
            fn ($m, $y) => RepairTicket::whereYear('created_at', $y)->whereMonth('created_at', $m)->count()
        );

        return [

            Stat::make(__('messages.monthly_revenue'), 'MAD ' . number_format($revenueCurrent, 2))
                ->description($this->trend($revenueCurrent, $revenuePrev))
                ->descriptionIcon($this->trendIcon($revenueCurrent, $revenuePrev))
                ->color($revenueCurrent >= $revenuePrev ? 'success' : 'danger')
                ->chart($revenueChart)
                ->icon('heroicon-o-banknotes'),

            Stat::make(__('messages.monthly_expenses'), 'MAD ' . number_format($expenseCurrent, 2))
                ->description($this->trend($expenseCurrent, $expensePrev))
                ->descriptionIcon($this->trendIcon($expenseCurrent, $expensePrev, true))
                ->color($expenseCurrent <= $expensePrev ? 'success' : 'danger')
                ->chart($expenseChart)
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make(__('messages.net_profit'), 'MAD ' . number_format($profitCurrent, 2))
                ->description($this->trend($profitCurrent, $profitPrev))
                ->descriptionIcon($this->trendIcon($profitCurrent, $profitPrev))
                ->color($profitCurrent >= 0 ? 'success' : 'danger')
                ->chart($profitChart)
                ->icon('heroicon-o-chart-bar'),

            Stat::make(__('messages.active_tickets'), $activeTickets)
                ->description(__('messages.repairs_in_progress'))
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning')
                ->chart($activeChart)
                ->icon('heroicon-o-wrench-screwdriver'),

        ];
    }

    private function monthlyTotals(\Closure $query): array
    {
        $year = now()->year;
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $y      = $year;
            $data[] = (float) $query($m, $y);
        }
        return $data;
    }

    private function trend(float $current, float $previous): string
    {
        if ($previous == 0) {
            return __('messages.no_previous_data');
        }
        $pct = round((($current - $previous) / abs($previous)) * 100, 1);
        $sign = $pct >= 0 ? '+' : '';
        return "{$sign}{$pct}% " . __('messages.vs_last_month');
    }

    private function trendIcon(float $current, float $previous, bool $invertColors = false): string
    {
        if ($current >= $previous) {
            return $invertColors ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-up';
        }
        return 'heroicon-m-arrow-trending-down';
    }
}
