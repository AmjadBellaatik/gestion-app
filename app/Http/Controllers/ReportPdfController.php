<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\LoginLog;
use App\Models\MotorcycleUnit;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Warranty;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportPdfController extends Controller
{
    private const ALLOWED_TYPES = [
        'sales', 'payments', 'clients', 'resellers', 'stock',
        'motorcycles', 'repairs', 'warranties', 'activity',
    ];

    public function generate(Request $request): Response
    {
        abort_unless(auth()->user()?->can('view_reports'), 403);

        // Validate and whitelist all query parameters before use
        $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', self::ALLOWED_TYPES)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        $type = $request->query('type');
        $from = Carbon::createFromFormat('Y-m-d', $request->query('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to   = Carbon::createFromFormat('Y-m-d', $request->query('to',   now()->endOfMonth()->toDateString()))->endOfDay();

        // Ensure date range is logical
        if ($from->greaterThan($to)) {
            abort(422, 'The from date must be before the to date.');
        }

        $periodLabel = $from->format('d M Y') . ' – ' . $to->format('d M Y');
        $company     = Company::find(session('company_id')) ?? Company::first();

        $data = match ($type) {
            'sales'       => $this->salesData($from, $to),
            'payments'    => $this->paymentsData($from, $to),
            'clients'     => $this->clientsData($from, $to),
            'resellers'   => $this->resellersData($from, $to),
            'stock'       => $this->stockData($from, $to),
            'motorcycles' => $this->motorcyclesData(),
            'repairs'     => $this->repairsData($from, $to),
            'warranties'  => $this->warrantiesData($from, $to),
            'activity'    => $this->activityData($from, $to),
        };

        $pdf = Pdf::loadView("reports.pdf.{$type}", array_merge($data, [
            'periodLabel' => $periodLabel,
            'from'        => $from,
            'to'          => $to,
            'company'     => $company,
        ]))->setPaper('a4', 'portrait');

        // Sanitize filename: only alphanumeric, dash, underscore, dot
        $filename = preg_replace('/[^a-z0-9\-_.]/', '', $type)
            . '-report-' . $from->format('Y-m-d') . '-' . $to->format('Y-m-d') . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function fmt(float $v): string
    {
        return number_format($v, 2, '.', ' ');
    }

    private function salesData(Carbon $from, Carbon $to): array
    {
        $sales        = Sale::with(['client', 'reseller'])->whereBetween('sale_date', [$from, $to])->orderByDesc('sale_date')->get();
        $totalRevenue = $sales->sum('total');
        $totalPaid    = $sales->sum('paid_amount');
        $totalUnpaid  = $sales->sum('remaining_amount');
        $totalDiscount = $sales->sum('discount');
        $avgOrder     = $sales->count() ? $totalRevenue / $sales->count() : 0.0;

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
        $clients       = Client::withCount('sales')->withSum('sales', 'total')->orderBy('name')->get();
        $totalClients  = $clients->count();
        $activeClients = $clients->where('is_active', true)->count();
        $blockedClients = $clients->where('is_blocked', true)->count();
        $persons       = $clients->where('client_type', 'person')->count();
        $companies     = $clients->where('client_type', 'company')->count();
        $newInPeriod   = Client::whereBetween('created_at', [$from, $to])->count();

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
        $rawProducts  = Product::orderBy('name')->get();
        $movements    = StockMovement::with('product')->whereBetween('created_at', [$from, $to])->latest()->get();

        $products = $rawProducts->map(function ($p) use ($movements) {
            $entries = $movements->where('product_id', $p->id)->whereIn('type', ['entry', 'in'])->sum('quantity');
            $exits   = $movements->where('product_id', $p->id)->whereIn('type', ['exit', 'out', 'sale'])->sum('quantity');
            $p->current_qty  = max(0, $p->quantity ?? ($p->stock ?? 0));
            $p->stock_value  = $p->current_qty * ($p->purchase_price ?? $p->selling_price ?? 0);
            $p->is_low       = $p->stock_alert > 0 && $p->current_qty <= $p->stock_alert;
            return $p;
        });

        $totalProducts    = $products->count();
        $lowStockCount    = $products->where('is_low', true)->count();
        $totalValue       = $products->sum('stock_value');
        $movementsCount   = $movements->count();
        $entriesCount     = $movements->whereIn('type', ['entry', 'in'])->count();
        $exitsCount       = $movements->whereIn('type', ['exit', 'out', 'sale'])->count();

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
        // ActivityLog has no CompanyScope — filter manually to prevent cross-tenant leakage.
        $activities      = ActivityLog::with('user')
            ->where('company_id', session('company_id'))
            ->whereBetween('created_at', [$from, $to])
            ->latest()->limit(500)->get();
        $logins          = LoginLog::whereBetween('created_at', [$from, $to])->latest()->limit(200)->get();
        $totalActivities = $activities->count();
        $uniqueUsers     = $activities->pluck('user_id')->unique()->filter()->count();
        $byModule        = $activities->groupBy('module')->map(fn ($g) => $g->count())->sortDesc();
        $successfulLogins = $logins->where('successful', true)->count();
        $failedLogins    = $logins->where('successful', false)->count();

        return compact('activities', 'logins', 'totalActivities', 'uniqueUsers', 'byModule', 'successfulLogins', 'failedLogins');
    }
}
