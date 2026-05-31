<?php

namespace App\Services\Dashboard;

use App\Models\Client;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\RepairTicket;
use App\Models\WarrantyClaim;
use App\Models\Transaction;
use App\Models\StockMovement;
use App\Models\Reseller;
use App\Models\Document;

class DashboardService
{
    public static function getStats(): array
    {
        return [

            'revenue' => Transaction::query()

                ->where('direction', 'in')

                ->sum('amount'),

            'expenses' => Transaction::query()

                ->where('direction', 'out')

                ->sum('amount'),

            'pending_invoices' => Document::query()

                ->where('status', 'pending')

                ->count(),

            'stock_alerts' => Product::query()

                ->where('stock_quantity', '<=', 5)

                ->count(),

            'latest_invoices' => Document::query()

                ->latest()

                ->take(5)

                ->get(),

            'latest_payments' => Payment::query()

                ->latest()

                ->take(5)

                ->get(),

            'top_products' => Product::query()

                ->orderByDesc('stock_quantity')

                ->take(5)

                ->get(),

            'repairs' => RepairTicket::query()

                ->count(),

            'pending_repairs' => RepairTicket::query()

                ->where('status', '!=', 'finished')

                ->count(),

            'warranty_repairs' => RepairTicket::query()

                ->whereHas('repairType', function ($q) {

                    $q->where(
                        'code',
                        'WARRANTY'
                    );

                })

                ->count(),

            'reseller_credits' => Reseller::query()

                ->sum('credit'),

            'monthly_sales' => Transaction::query()

                ->where('type', 'sale')

                ->selectRaw('MONTH(transaction_date) as month')

                ->selectRaw('SUM(amount) as total')

                ->groupBy('month')

                ->pluck('total', 'month'),

        ];
    }
}