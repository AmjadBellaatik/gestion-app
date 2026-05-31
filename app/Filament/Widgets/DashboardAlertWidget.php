<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Reseller;
use App\Models\Sale;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardAlertWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // ── Unpaid Sales (remaining amount) ───────────────────────────────────
        $unpaidAmount = Sale::whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('remaining_amount');

        $unpaidCount = Sale::whereIn('payment_status', ['unpaid', 'partial'])->count();

        // ── Reseller Debts ────────────────────────────────────────────────────
        $resellerDebts = Reseller::sum('current_debt');

        // ── Open Warranty Repairs ─────────────────────────────────────────────
        $warrantyOpen = RepairTicket::where('is_warranty', true)
            ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->count();

        // ── Low Stock Products ────────────────────────────────────────────────
        $lowStockCount = $this->getLowStockCount();

        return [

            Stat::make(__('messages.unpaid_invoices'), 'MAD ' . number_format($unpaidAmount, 2))
                ->description($unpaidCount . ' ' . __('messages.pending_customer_payments'))
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($unpaidAmount > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-credit-card'),

            Stat::make(__('messages.reseller_debts'), 'MAD ' . number_format($resellerDebts, 2))
                ->description(__('messages.total_reseller_unpaid'))
                ->descriptionIcon($resellerDebts > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($resellerDebts > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-building-storefront'),

            Stat::make(__('messages.warranty_repairs'), $warrantyOpen)
                ->description(__('messages.repairs_under_warranty'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($warrantyOpen > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-shield-check'),

            Stat::make(__('messages.low_stock'), $lowStockCount)
                ->description(__('messages.products_below_threshold'))
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowStockCount > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-archive-box-x-mark'),

        ];
    }

    private function getLowStockCount(): int
    {
        return Product::where('stock_alert', '>', 0)
            ->withSum(['stockMovements as stock_in' => fn ($q) => $q->whereIn('type', ['entry', 'in', 'transfer', 'adjustment', 'return'])
                ->orWhereIn('movement_type', ['purchase', 'adjustment', 'return'])], 'quantity')
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->whereIn('type', ['exit', 'out'])
                ->orWhereIn('movement_type', ['sale', 'repair', 'repair_usage'])], 'quantity')
            ->get()
            ->filter(fn ($p) => (($p->stock_in ?? 0) - ($p->stock_out ?? 0)) <= $p->stock_alert)
            ->count();
    }
}
