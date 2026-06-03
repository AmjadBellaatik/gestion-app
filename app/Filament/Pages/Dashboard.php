<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Technician;
use Carbon\Carbon;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;

    public string $period = 'this_month';
    public ?string $dateFrom = null;
    public ?string $dateTo   = null;

    public function mount(): void
    {
        [$from, $to]   = $this->periodRange('this_month');
        $this->dateFrom = $from->toDateString();
        $this->dateTo   = $to->toDateString();
    }

    public function setPeriod(string $p): void
    {
        $this->period = $p;

        if ($p !== 'custom') {
            [$from, $to]   = $this->periodRange($p);
            $this->dateFrom = $from->toDateString();
            $this->dateTo   = $to->toDateString();
        }
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.dashboard');
    }

    public function getTitle(): string
    {
        return __('messages.dashboard');
    }

    public function getWidgets(): array
    {
        return [];
    }

    // ──────────────────────────────────────────────────────────────
    // Period helpers
    // ──────────────────────────────────────────────────────────────

    private function periodRange(string $period): array
    {
        return match ($period) {
            'today'         => [now()->copy()->startOfDay(),                        now()->copy()->endOfDay()],
            'yesterday'     => [now()->copy()->subDay()->startOfDay(),              now()->copy()->subDay()->endOfDay()],
            'this_week'     => [now()->copy()->startOfWeek(),                       now()->copy()->endOfWeek()],
            'last_week'     => [now()->copy()->subWeek()->startOfWeek(),            now()->copy()->subWeek()->endOfWeek()],
            'this_month'    => [now()->copy()->startOfMonth(),                      now()->copy()->endOfMonth()],
            'last_month'    => [now()->copy()->subMonth()->startOfMonth(),          now()->copy()->subMonth()->endOfMonth()],
            'last_3_months' => [now()->copy()->subMonths(3)->startOfMonth(),        now()->copy()->endOfMonth()],
            'this_year'     => [now()->copy()->startOfYear(),                       now()->copy()->endOfYear()],
            default         => [now()->copy()->startOfMonth(),                      now()->copy()->endOfMonth()],
        };
    }

    private function resolveRange(): array
    {
        if ($this->period === 'custom' && $this->dateFrom && $this->dateTo) {
            return [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ];
        }

        return $this->periodRange($this->period);
    }

    // ──────────────────────────────────────────────────────────────
    // View data — called on every Livewire render
    // ──────────────────────────────────────────────────────────────

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        $year  = $from->year;
        $month = $from->month;

        // ── comparison period (same length, directly preceding) ──
        $days    = max(1, (int) $from->diffInDays($to) + 1);
        $prevFrom = $from->copy()->subDays($days);
        $prevTo   = $from->copy()->subSecond();

        $revenueCurrent = (float) Sale::whereBetween('created_at', [$from, $to])->sum('total');
        $revenuePrev    = (float) Sale::whereBetween('created_at', [$prevFrom, $prevTo])->sum('total');

        $unpaidAmount = (float) Sale::whereIn('payment_status', ['unpaid', 'partial'])->sum('remaining_amount');
        $unpaidCount  = Sale::whereIn('payment_status', ['unpaid', 'partial'])->count();

        $resellerDebts = (float) Reseller::sum('current_debt');

        $warrantyOpen = RepairTicket::where('is_warranty', true)
            ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->count();

        $lowStockCount = Product::where('stock_alert', '>', 0)
            ->withSum(['stockMovements as stock_in' => fn ($q) => $q->whereIn('type', ['entry', 'in', 'transfer', 'adjustment', 'return'])], 'quantity')
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->whereIn('type', ['exit', 'out'])], 'quantity')
            ->get()
            ->filter(fn ($p) => (($p->stock_in ?? 0) - ($p->stock_out ?? 0)) <= $p->stock_alert)
            ->count();

        $completedMonth = RepairTicket::where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->count();

        $completedTotal = RepairTicket::whereIn('status', ['completed', 'delivered'])->count();
        $activeTech     = Technician::count();

        $avgRaw = RepairTicket::whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to])
            ->get()
            ->avg(fn ($r) => $r->started_at->diffInHours($r->completed_at));
        $avgHours = round((float) ($avgRaw ?? 0), 1);

        $stockValuation = (float) Product::withSum(
            ['stockMovements as stock_in' => fn ($q) => $q->whereIn('type', ['entry', 'in', 'transfer', 'adjustment', 'return'])],
            'quantity'
        )
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->whereIn('type', ['exit', 'out'])], 'quantity')
            ->get()
            ->sum(fn ($p) => max(0, (($p->stock_in ?? 0) - ($p->stock_out ?? 0))) * (float) $p->purchase_price);

        // Gross profit on product-based items in the selected range
        $rangeSaleIds = Sale::whereBetween('created_at', [$from, $to])->pluck('id');
        $grossProfit  = (float) SaleItem::whereIn('sale_id', $rangeSaleIds)
            ->whereNotNull('product_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(SUM((sale_items.unit_price - COALESCE(products.purchase_price, 0)) * sale_items.quantity), 0) as profit')
            ->value('profit');

        // Monthly revenue for the chart — always full current year, period-independent
        $chartYear      = now()->year;
        $revenueByMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenueByMonth[] = (float) Sale::whereYear('created_at', $chartYear)
                ->whereMonth('created_at', $m)
                ->sum('total');
        }

        // Repair counts — all-time for the doughnut chart
        $repairCounts = [
            RepairTicket::where('status', 'open')->count(),
            RepairTicket::where('status', 'diagnostic')->count(),
            RepairTicket::where('status', 'assigned')->count(),
            RepairTicket::where('status', 'in_progress')->count(),
            RepairTicket::where('status', 'completed')->count(),
            RepairTicket::where('status', 'delivered')->count(),
            RepairTicket::where('status', 'cancelled')->count(),
        ];

        $periodLabel = $from->format('d M Y') . ' – ' . $to->format('d M Y');

        return compact(
            'year', 'month', 'chartYear',
            'revenueCurrent', 'revenuePrev',
            'grossProfit', 'unpaidAmount', 'unpaidCount',
            'resellerDebts', 'warrantyOpen', 'lowStockCount',
            'completedMonth', 'completedTotal',
            'activeTech', 'avgHours', 'stockValuation',
            'revenueByMonth', 'repairCounts', 'periodLabel'
        );
    }
}
