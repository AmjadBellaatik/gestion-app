<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;

use App\Models\LoginLog;
use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\Technician;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | MySQL / MariaDB compatibility
        |--------------------------------------------------------------------------
        */
        Schema::defaultStringLength(191);

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD VIEW COMPOSER
        |--------------------------------------------------------------------------
        */

        \View::composer('filament.pages.dashboard', function (\Illuminate\View\View $view) {
            $year      = now()->year;
            $month     = now()->month;
            $prev      = now()->subMonth();
            $prevMonth = $prev->month;
            $prevYear  = $prev->year;

            $revenueCurrent = (float) Sale::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total');

            $revenuePrev = (float) Sale::whereYear('created_at', $prevYear)
                ->whereMonth('created_at', $prevMonth)
                ->sum('total');

            $unpaidAmount = (float) Sale::whereIn('payment_status', ['unpaid', 'partial'])
                ->sum('remaining_amount');

            $unpaidCount = Sale::whereIn('payment_status', ['unpaid', 'partial'])
                ->count();

            $resellerDebts = (float) Reseller::sum('current_debt');

            $warrantyOpen = RepairTicket::where('is_warranty', true)
                ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
                ->count();

            $lowStockCount = Product::where('stock_alert', '>', 0)
                ->withSum([
                    'stockMovements as stock_in' => fn ($q) => $q->whereIn(
                        'type',
                        ['entry', 'in', 'transfer', 'adjustment', 'return']
                    )
                ], 'quantity')
                ->withSum([
                    'stockMovements as stock_out' => fn ($q) => $q->whereIn(
                        'type',
                        ['exit', 'out']
                    )
                ], 'quantity')
                ->get()
                ->filter(fn ($p) => (($p->stock_in ?? 0) - ($p->stock_out ?? 0)) <= $p->stock_alert)
                ->count();

            $completedMonth = RepairTicket::where('status', 'completed')
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->count();

            $completedTotal = RepairTicket::whereIn('status', ['completed', 'delivered'])
                ->count();

            $activeTech = Technician::count();

            $avgRaw = RepairTicket::whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->whereYear('completed_at', $year)
                ->get()
                ->avg(fn ($r) => $r->started_at->diffInHours($r->completed_at));

            $avgHours = round((float) ($avgRaw ?? 0), 1);

            $stockValuation = (float) Product::withSum([
                'stockMovements as stock_in' => fn ($q) => $q->whereIn(
                    'type',
                    ['entry', 'in', 'transfer', 'adjustment', 'return']
                )
            ], 'quantity')
                ->withSum([
                    'stockMovements as stock_out' => fn ($q) => $q->whereIn(
                        'type',
                        ['exit', 'out']
                    )
                ], 'quantity')
                ->get()
                ->sum(fn ($p) => max(
                    0,
                    (($p->stock_in ?? 0) - ($p->stock_out ?? 0))
                ) * (float) $p->purchase_price);

            $revenueByMonth = [];

            for ($m = 1; $m <= 12; $m++) {
                $revenueByMonth[] = (float) Sale::whereYear('created_at', $year)
                    ->whereMonth('created_at', $m)
                    ->sum('total');
            }

            $repairCounts = [
                RepairTicket::where('status', 'open')->count(),
                RepairTicket::where('status', 'diagnostic')->count(),
                RepairTicket::where('status', 'assigned')->count(),
                RepairTicket::where('status', 'in_progress')->count(),
                RepairTicket::where('status', 'completed')->count(),
                RepairTicket::where('status', 'delivered')->count(),
                RepairTicket::where('status', 'cancelled')->count(),
            ];

            $view->with(compact(
                'year',
                'revenueCurrent',
                'revenuePrev',
                'unpaidAmount',
                'unpaidCount',
                'resellerDebts',
                'warrantyOpen',
                'lowStockCount',
                'completedMonth',
                'completedTotal',
                'activeTech',
                'avgHours',
                'stockValuation',
                'revenueByMonth',
                'repairCounts'
            ));
        });

        \Event::listen(Login::class, function (Login $event) {
            LoginLog::create([
                'user_id'      => $event->user->id,
                'email'        => $event->user->email,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'successful'   => true,
                'logged_in_at' => now(),
            ]);
        });

        \Event::listen(Failed::class, function (Failed $event) {
            LoginLog::create([
                'email'      => request('email'),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'successful' => false,
            ]);
        });
    }
}