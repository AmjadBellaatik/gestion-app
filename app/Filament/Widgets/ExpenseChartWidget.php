<?php

namespace App\Filament\Widgets;

use App\Models\Expense;

use Filament\Widgets\ChartWidget;

class ExpenseChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 2;

    protected ?string $heading = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return __('messages.monthly_expenses') . ' — ' . now()->year;
    }

    protected function getData(): array
    {
        $year = now()->year;
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $data[] = (float) Expense::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('amount');
        }

        return [
            'datasets' => [[
                'label'           => __('messages.expenses'),
                'data'            => $data,
                'backgroundColor' => 'rgba(239,68,68,0.75)',
                'borderColor'     => '#ef4444',
                'borderWidth'     => 1,
                'borderRadius'    => 6,
            ]],
            'labels' => $this->monthLabels(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
