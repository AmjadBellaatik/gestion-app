<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\Reseller;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ResellerCreditsReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.reseller-credits-report';

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
        return __('messages.resellers_report');
    }

    public function getTitle(): string
    {
        return __('messages.resellers_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('resellers')];
    }

    public function getViewData(): array
    {
        $resellers = Reseller::withCount('sales')
            ->orderByDesc('current_debt')
            ->get();

        $totalResellers = $resellers->count();
        $totalOrders    = $resellers->sum('total_orders');
        $totalPaid      = $resellers->sum('total_paid');
        $totalDebt      = $resellers->sum('current_debt');
        $withDebt       = $resellers->where('current_debt', '>', 0)->count();

        $periodLabel = $this->periodLabel();

        return compact(
            'resellers', 'totalResellers', 'totalOrders',
            'totalPaid', 'totalDebt', 'withDebt', 'periodLabel'
        );
    }
}
