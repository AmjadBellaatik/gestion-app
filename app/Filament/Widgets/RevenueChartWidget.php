<?php

namespace App\Filament\Widgets;

use App\Models\Sale;

use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 2;

    protected ?string $heading = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return __('messages.monthly_revenue') . ' — ' . now()->year;
    }

    protected function getData(): array
    {
        $year = now()->year;
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $data[] = (float) Sale::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total');
        }

        return [
            'datasets' => [[
                'label'           => __('messages.revenue'),
                'data'            => $data,
                'borderColor'     => '#10b981',
                'backgroundColor' => 'rgba(16,185,129,0.12)',
                'borderWidth'     => 2,
                'fill'            => true,
                'tension'         => 0.4,
                'pointRadius'     => 4,
                'pointBackgroundColor' => '#10b981',
            ]],
            'labels' => $this->monthLabels(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['callback' => 'function(v){return "MAD "+v.toLocaleString();}'],
                ],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }

    private function monthLabels(): array
    {
        return [
            __('messages.jan'), __('messages.feb'), __('messages.mar'),
            __('messages.apr'), __('messages.may'), __('messages.jun'),
            __('messages.jul'), __('messages.aug'), __('messages.sep'),
            __('messages.oct'), __('messages.nov'), __('messages.dec'),
        ];
    }
}
