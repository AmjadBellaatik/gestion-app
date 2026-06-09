<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\RepairTicket;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class RepairsReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?int $navigationSort = 7;
    protected string $view = 'filament.pages.repairs-report';

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
        return __('messages.repairs_report');
    }

    public function getTitle(): string
    {
        return __('messages.repairs_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('repairs')];
    }

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        $repairs = RepairTicket::with(['client', 'technician', 'motorcycleUnit.motorcycleModel'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $totalRepairs    = $repairs->count();
        $openCount       = $repairs->whereIn('status', ['open', 'diagnostic', 'waiting_approval', 'approved', 'waiting_parts', 'in_progress'])->count();
        $completedCount  = $repairs->whereIn('status', ['completed', 'delivered', 'closed'])->count();
        $warrantyCount   = $repairs->where('is_warranty', true)->count();
        $cancelledCount  = $repairs->where('status', 'cancelled')->count();
        $totalRevenue    = $repairs->sum('total_cost');
        $avgTime         = round($repairs
            ->filter(fn ($r) => $r->started_at && $r->completed_at)
            ->avg(fn ($r) => $r->started_at->diffInHours($r->completed_at)) ?? 0, 1);

        $periodLabel = $this->periodLabel();

        return compact(
            'repairs', 'totalRepairs', 'openCount', 'completedCount',
            'warrantyCount', 'cancelledCount', 'totalRevenue', 'avgTime', 'periodLabel'
        );
    }
}
