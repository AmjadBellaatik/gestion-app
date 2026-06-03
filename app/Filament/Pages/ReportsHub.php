<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\LoginLog;
use App\Models\MotorcycleUnit;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Warranty;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;

class ReportsHub extends Page
{
    protected string $view = 'filament.pages.reports-hub';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?int $navigationSort = 50;

    public string  $period     = 'this_month';
    public ?string $dateFrom   = null;
    public ?string $dateTo     = null;
    public ?string $reportType = null;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.reports');
    }

    public function getTitle(): string
    {
        return __('messages.reports');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    public function mount(): void
    {
        [$from, $to]   = $this->periodRange('this_month');
        $this->dateFrom = $from->toDateString();
        $this->dateTo   = $to->toDateString();
    }

    public function setReportType(?string $type): void
    {
        $this->reportType = $type;
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

    protected function periodRange(string $period): array
    {
        return match ($period) {
            'today'         => [now()->startOfDay(),                              now()->endOfDay()],
            'yesterday'     => [now()->subDay()->startOfDay(),                    now()->subDay()->endOfDay()],
            'this_week'     => [now()->startOfWeek(),                             now()->endOfWeek()],
            'this_month'    => [now()->startOfMonth(),                            now()->endOfMonth()],
            'last_month'    => [now()->subMonth()->startOfMonth(),                now()->subMonth()->endOfMonth()],
            'last_3_months' => [now()->subMonths(3)->startOfMonth(),              now()->endOfMonth()],
            'this_year'     => [now()->startOfYear(),                             now()->endOfYear()],
            default         => [now()->startOfMonth(),                            now()->endOfMonth()],
        };
    }

    protected function resolveRange(): array
    {
        if ($this->period === 'custom' && $this->dateFrom && $this->dateTo) {
            return [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ];
        }

        return $this->periodRange($this->period);
    }

    protected function periodLabel(): string
    {
        [$from, $to] = $this->resolveRange();

        return $from->format('d M Y') . ' – ' . $to->format('d M Y');
    }

    protected function getHeaderActions(): array
    {
        if (! $this->reportType) {
            return [];
        }

        return [
            Action::make('export_pdf')
                ->label(__('messages.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('reports.pdf', [
                    'type' => $this->reportType,
                    'from' => $this->dateFrom,
                    'to'   => $this->dateTo,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getViewData(): array
    {
        $periodLabel = $this->periodLabel();

        if (! $this->reportType) {
            return compact('periodLabel');
        }

        [$from, $to] = $this->resolveRange();

        $data = match ($this->reportType) {
            'sales'       => $this->salesData($from, $to),
            'payments'    => $this->paymentsData($from, $to),
            'clients'     => $this->clientsData($from, $to),
            'resellers'   => $this->resellersData($from, $to),
            'stock'       => $this->stockData($from, $to),
            'motorcycles' => $this->motorcyclesData(),
            'repairs'     => $this->repairsData($from, $to),
            'warranties'  => $this->warrantiesData($from, $to),
            'activity'    => $this->activityData($from, $to),
            default       => [],
        };

        return array_merge($data, compact('periodLabel'));
    }

    private function salesData(Carbon $from, Carbon $to): array
    {
        $sales         = Sale::with(['client', 'reseller'])->whereBetween('created_at', [$from, $to])->latest()->get();
        $totalRevenue  = $sales->sum('total');
        $totalPaid     = $sales->sum('paid_amount');
        $totalUnpaid   = $sales->sum('remaining_amount');
        $totalDiscount = $sales->sum('discount');
        $avgOrder      = $sales->count() ? $totalRevenue / $sales->count() : 0.0;

        return compact('sales', 'totalRevenue', 'totalPaid', 'totalUnpaid', 'totalDiscount', 'avgOrder');
    }

    private function paymentsData(Carbon $from, Carbon $to): array
    {
        $payments       = Payment::with(['sale.client', 'sale.reseller'])->whereBetween('created_at', [$from, $to])->latest()->get();
        $totalCollected = $payments->where('status', 'paid')->sum('amount');
        $totalPending   = $payments->where('status', 'pending_validation')->sum('amount');
        $cashTotal      = $payments->where('payment_method', 'cash')->sum('amount');
        $cardTotal      = $payments->where('payment_method', 'card')->sum('amount');
        $chequeTotal    = $payments->where('payment_method', 'cheque')->sum('amount');
        $transferTotal  = $payments->where('payment_method', 'bank_transfer')->sum('amount');

        return compact('payments', 'totalCollected', 'totalPending', 'cashTotal', 'cardTotal', 'chequeTotal', 'transferTotal');
    }

    private function clientsData(Carbon $from, Carbon $to): array
    {
        $clients        = Client::withCount('sales')->withSum('sales', 'total')->orderBy('name')->get();
        $totalClients   = $clients->count();
        $activeClients  = $clients->where('is_active', true)->count();
        $blockedClients = $clients->where('is_blocked', true)->count();
        $persons        = $clients->where('client_type', 'person')->count();
        $companies      = $clients->where('client_type', 'company')->count();
        $newInPeriod    = Client::whereBetween('created_at', [$from, $to])->count();

        return compact('clients', 'totalClients', 'activeClients', 'blockedClients', 'persons', 'companies', 'newInPeriod');
    }

    private function resellersData(Carbon $from, Carbon $to): array
    {
        $resellers = Reseller::withCount(['sales as total_orders' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->withSum(['payments as total_paid' => fn ($q) => $q->where('status', 'paid')->whereBetween('created_at', [$from, $to])], 'amount')
            ->orderBy('name')
            ->get()
            ->map(function ($r) {
                $r->current_debt = max(0, $r->sales()->sum('remaining_amount'));
                return $r;
            });

        $totalResellers = $resellers->count();
        $totalOrders    = $resellers->sum('total_orders');
        $totalPaid      = $resellers->sum('total_paid');
        $totalDebt      = $resellers->sum('current_debt');
        $withDebt       = $resellers->where('current_debt', '>', 0)->count();

        return compact('resellers', 'totalResellers', 'totalOrders', 'totalPaid', 'totalDebt', 'withDebt');
    }

    private function stockData(Carbon $from, Carbon $to): array
    {
        $rawProducts = Product::orderBy('name')->get();
        $movements   = StockMovement::with('product')->whereBetween('created_at', [$from, $to])->latest()->get();

        $products = $rawProducts->map(function ($p) {
            $p->current_qty = max(0, $p->quantity ?? ($p->stock ?? 0));
            $p->stock_value = $p->current_qty * ($p->purchase_price ?? $p->selling_price ?? 0);
            $p->is_low      = $p->stock_alert > 0 && $p->current_qty <= $p->stock_alert;
            return $p;
        });

        $totalProducts  = $products->count();
        $lowStockCount  = $products->where('is_low', true)->count();
        $totalValue     = $products->sum('stock_value');
        $movementsCount = $movements->count();
        $entriesCount   = $movements->whereIn('type', ['entry', 'in'])->count();
        $exitsCount     = $movements->whereIn('type', ['exit', 'out', 'sale'])->count();

        return compact('products', 'movements', 'totalProducts', 'lowStockCount', 'totalValue', 'movementsCount', 'entriesCount', 'exitsCount');
    }

    private function motorcyclesData(): array
    {
        $units      = MotorcycleUnit::with(['motorcycleModel', 'client'])->orderByDesc('id')->get();
        $totalUnits = $units->count();
        $inStock    = $units->whereIn('status', ['in_stock', 'available'])->count();
        $onHold     = $units->where('status', 'on_hold')->count();
        $sold       = $units->where('status', 'sold')->count();

        return compact('units', 'totalUnits', 'inStock', 'onHold', 'sold');
    }

    private function repairsData(Carbon $from, Carbon $to): array
    {
        $repairs        = RepairTicket::with(['client', 'technician', 'motorcycleUnit.motorcycleModel'])->whereBetween('created_at', [$from, $to])->latest()->get();
        $totalRepairs   = $repairs->count();
        $openCount      = $repairs->whereIn('status', ['open', 'diagnostic', 'assigned', 'in_progress'])->count();
        $completedCount = $repairs->whereIn('status', ['completed', 'delivered'])->count();
        $warrantyCount  = $repairs->where('is_warranty', true)->count();
        $cancelledCount = $repairs->where('status', 'cancelled')->count();
        $totalRevenue   = $repairs->sum('total_cost');
        $avgTime        = round($repairs->filter(fn ($r) => $r->started_at && $r->completed_at)->avg(fn ($r) => $r->started_at->diffInHours($r->completed_at)) ?? 0, 1);

        return compact('repairs', 'totalRepairs', 'openCount', 'completedCount', 'warrantyCount', 'cancelledCount', 'totalRevenue', 'avgTime');
    }

    private function warrantiesData(Carbon $from, Carbon $to): array
    {
        $warranties   = Warranty::with(['client', 'product', 'motorcycleUnit.motorcycleModel'])->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])->orderByDesc('start_date')->get();
        $activeCount  = $warranties->filter(fn ($w) => $w->status === 'active')->count();
        $expiredCount = $warranties->filter(fn ($w) => $w->status === 'expired')->count();
        $expiringSoon = $warranties->filter(fn ($w) => $w->status === 'active' && $w->end_date && $w->end_date->diffInDays(now()) <= 30 && $w->end_date->isFuture())->count();

        return compact('warranties', 'activeCount', 'expiredCount', 'expiringSoon');
    }

    private function activityData(Carbon $from, Carbon $to): array
    {
        $activities       = ActivityLog::with('user')->whereBetween('created_at', [$from, $to])->latest()->limit(500)->get();
        $logins           = LoginLog::whereBetween('created_at', [$from, $to])->latest()->limit(200)->get();
        $totalActivities  = $activities->count();
        $uniqueUsers      = $activities->pluck('user_id')->unique()->filter()->count();
        $byModule         = $activities->groupBy('module')->map(fn ($g) => $g->count())->sortDesc();
        $successfulLogins = $logins->where('successful', true)->count();
        $failedLogins     = $logins->where('successful', false)->count();

        return compact('activities', 'logins', 'totalActivities', 'uniqueUsers', 'byModule', 'successfulLogins', 'failedLogins');
    }
}
