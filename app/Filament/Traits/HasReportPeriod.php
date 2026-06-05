<?php

namespace App\Filament\Traits;

use Carbon\Carbon;
use Filament\Actions\Action;

trait HasReportPeriod
{
    public string $period   = 'this_month';
    public ?string $dateFrom = null;
    public ?string $dateTo   = null;

    public function initializePeriod(): void
    {
        [$from, $to]   = $this->periodRange('this_month');
        $this->dateFrom = $from->toDateString();
        $this->dateTo   = $to->toDateString();
    }

    public function setPeriod(string $p): void
    {
        $this->period = $p;

        if ($p !== 'custom') {
            [$from, $to]   = $this->periodRange($p);
            $this->dateFrom = $from->toDateString();
            $this->dateTo   = $to->toDateString();
        }
    }

    protected function periodRange(string $period): array
    {
        return match ($period) {
            'today'         => [now()->copy()->startOfDay(),                        now()->copy()->endOfDay()],
            'yesterday'     => [now()->copy()->subDay()->startOfDay(),              now()->copy()->subDay()->endOfDay()],
            'this_week'     => [now()->copy()->startOfWeek(),                       now()->copy()->endOfWeek()],
            'last_week'     => [now()->copy()->subWeek()->startOfWeek(),            now()->copy()->subWeek()->endOfWeek()],
            'this_month'    => [now()->copy()->startOfMonth(),                      now()->copy()->endOfMonth()],
            'last_month'    => [now()->copy()->subMonth()->startOfMonth(),          now()->copy()->subMonth()->endOfMonth()],
            'last_3_months' => [now()->copy()->subMonths(3)->startOfMonth(),        now()->copy()->endOfMonth()],
            'this_year'     => [now()->copy()->startOfYear(),                       now()->copy()->endOfYear()],
            default         => [now()->copy()->startOfMonth(),                      now()->copy()->endOfMonth()],
        };
    }

    protected function resolveRange(): array
    {
        if ($this->period === 'custom' && $this->dateFrom && $this->dateTo) {
            return [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ];
        }

        return $this->periodRange($this->period);
    }

    protected function periodLabel(): string
    {
        [$from, $to] = $this->resolveRange();
        /* Use -u-nu-latn extension for Arabic so month names stay Arabic
           but day/year digits remain Latin (0-9).                        */
        $locale = app()->getLocale();
        $carbonLocale = str_starts_with($locale, 'ar') ? 'ar-u-nu-latn' : $locale;

        return $from->locale($carbonLocale)->isoFormat('D MMM YYYY')
             . ' – '
             . $to->locale($carbonLocale)->isoFormat('D MMM YYYY');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    protected function pdfExportAction(string $reportType): Action
    {
        return Action::make('export_pdf')
            ->label(__('messages.export_pdf'))
            ->icon('heroicon-o-document-arrow-down')
            ->color('danger')
            ->url(fn () => route('reports.pdf', [
                'type' => $reportType,
                'from' => $this->dateFrom,
                'to'   => $this->dateTo,
            ]))
            ->openUrlInNewTab();
    }
}
