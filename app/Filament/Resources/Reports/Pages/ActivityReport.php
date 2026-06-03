<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\ActivityLog;
use App\Models\LoginLog;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ActivityReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 9;
    protected string $view = 'filament.pages.activity-report';

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
        return __('messages.activity_report');
    }

    public function getTitle(): string
    {
        return __('messages.activity_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('activity')];
    }

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        $activities = ActivityLog::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(500)
            ->get();

        $logins = LoginLog::whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(200)
            ->get();

        $totalActivities  = $activities->count();
        $uniqueUsers      = $activities->pluck('user_id')->unique()->filter()->count();
        $byModule         = $activities->groupBy('module')
            ->map(fn ($g) => $g->count())
            ->sortDesc();
        $successfulLogins = $logins->where('successful', true)->count();
        $failedLogins     = $logins->where('successful', false)->count();

        $periodLabel = $this->periodLabel();

        return compact(
            'activities', 'logins', 'totalActivities', 'uniqueUsers',
            'byModule', 'successfulLogins', 'failedLogins', 'periodLabel'
        );
    }
}
