<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\MotorcycleUnit;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class MotorcyclesReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.motorcycles-report';

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
        return __('messages.motorcycles_report');
    }

    public function getTitle(): string
    {
        return __('messages.motorcycles_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('motorcycles')];
    }

    public function getViewData(): array
    {
        $units = MotorcycleUnit::with(['motorcycleModel', 'client'])
            ->orderByDesc('id')
            ->get();

        $totalUnits  = $units->count();
        $inStock     = $units->whereIn('status', ['in_stock', 'available'])->count();
        $onHold      = $units->where('status', 'on_hold')->count();
        $sold        = $units->where('status', 'sold')->count();

        $periodLabel = $this->periodLabel();

        return compact(
            'units', 'totalUnits', 'inStock', 'onHold', 'sold', 'periodLabel'
        );
    }
}
