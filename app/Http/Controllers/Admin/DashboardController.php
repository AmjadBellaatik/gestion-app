<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // All model queries below are automatically scoped to session('company_id')
        // via CompanyScope (fail-closed) — no cross-tenant data is returned.
        abort_unless(auth()->check(), 403);

        $revenue = Payment::sum('amount');

        $pendingInvoices = Invoice::where('status', 'pending')->count();

        $stockAlerts = Product::whereColumn('stock', '<=', 'alert_stock')->get();

        $latestInvoices = Invoice::latest()->take(5)->get();

        $latestPayments = Payment::latest()->take(5)->get();

        $topProducts = Product::orderBy('sold_count', 'desc')
            ->take(5)
            ->get();

        $monthlyRevenue = Payment::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month');

        return view('admin.dashboard', compact(
            'revenue',
            'pendingInvoices',
            'stockAlerts',
            'latestInvoices',
            'latestPayments',
            'topProducts',
            'monthlyRevenue'
        ));
    }
}