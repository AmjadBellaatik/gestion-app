<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\Warranty;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class WarrantyReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 8;
    protected string $view = 'filament.pages.warranty-report';

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
        return __('messages.warranty_report');
    }

    public function getTitle(): string
    {
        return __('messages.warranty_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('warranties')];
    }

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        $warranties = Warranty::with(['client', 'product', 'motorcycleUnit.motorcycleModel'])
            ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('start_date')
            ->get();

        $activeCount    = $warranties->filter(fn ($w) => $w->status === 'active')->count();
        $expiredCount   = $warranties->filter(fn ($w) => $w->status === 'expired')->count();
        $expiringSoon   = $warranties->filter(fn ($w) =>
            $w->status === 'active' && $w->end_date && $w->end_date->diffInDays(now()) <= 30 && $w->end_date->isFuture()
        )->count();

        $periodLabel = $this->periodLabel();

        return compact(
            'warranties', 'activeCount', 'expiredCount', 'expiringSoon', 'periodLabel'
        );
    }
}
