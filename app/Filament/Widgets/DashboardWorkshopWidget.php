<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Technician;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardWorkshopWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $year  = now()->year;
        $month = now()->month;

        // ── Completed Repairs This Month ──────────────────────────────────────
        $completedMonth = RepairTicket::where('status', 'completed')
            ->whereYear('completed_at', $year)
            ->whereMonth('completed_at', $month)
            ->count();

        $completedTotal = RepairTicket::whereIn('status', ['completed', 'delivered', 'closed'])->count();

        // ── Active Technicians ────────────────────────────────────────────────
        $activeTechnicians = Technician::count();

        // ── Average Repair Time (hours, using started_at → completed_at) ──────
        $avgHours = RepairTicket::whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->whereYear('completed_at', $year)
            ->get()
            ->avg(fn ($r) => $r->started_at->diffInHours($r->completed_at));

        // ── Total Stock Valuation ─────────────────────────────────────────────
        $stockValuation = Product::withSum(['stockMovements as stock_in' => fn ($q) => $q->whereIn('type', ['entry', 'in', 'transfer', 'adjustment', 'return'])], 'quantity')
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->whereIn('type', ['exit', 'out'])], 'quantity')
            ->get()
            ->sum(fn ($p) => max(0, (($p->stock_in ?? 0) - ($p->stock_out ?? 0))) * (float) $p->purchase_price);

        return [

            Stat::make(__('messages.completed_repairs'), $completedMonth)
                ->description($completedTotal . ' ' . __('messages.total_completed_repairs'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make(__('messages.technicians'), $activeTechnicians)
                ->description(__('messages.active_workshop_staff'))
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make(__('messages.average_repair_time'), round($avgHours ?? 0, 1) . ' h')
                ->description(__('messages.average_completion_time'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make(__('messages.stock_valuation'), 'MAD ' . number_format($stockValuation, 2))
                ->description(__('messages.total_inventory_value'))
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->icon('heroicon-o-cube'),

        ];
    }
}
