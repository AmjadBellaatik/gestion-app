<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\Sale;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class SalesReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.sales-report';

    public function mount(): void
    {
        $this->initializePeriod();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('messages.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.sales_report');
    }

    public function getTitle(): string
    {
        return __('messages.sales_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('sales')];
    }

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        // Business reporting uses the effective sale_date, not the DB timestamp.
        $sales = Sale::with(['client', 'reseller'])
            ->whereBetween('sale_date', [$from, $to])
            ->orderByDesc('sale_date')
            ->get();

        $totalRevenue  = $sales->sum('total');
        $totalDiscount = $sales->sum('discount');
        $totalPaid     = $sales->sum('paid_amount');
        $totalUnpaid   = $sales->sum('remaining_amount');
        $avgOrder      = $sales->count() > 0 ? round($totalRevenue / $sales->count(), 2) : 0;

        $periodLabel = $this->periodLabel();

        return compact(
            'sales', 'totalRevenue', 'totalDiscount',
            'totalPaid', 'totalUnpaid', 'avgOrder', 'periodLabel'
        );
    }
}
