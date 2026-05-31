<?php

namespace App\Filament\Widgets;

use App\Models\RepairTicket;

use Filament\Widgets\ChartWidget;

class RepairChartWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('messages.repair_status_analytics');
    }

    protected function getData(): array
    {
        $counts = [
            RepairTicket::where('status', 'open')->count(),
            RepairTicket::where('status', 'diagnostic')->count(),
            RepairTicket::where('status', 'assigned')->count(),
            RepairTicket::where('status', 'in_progress')->count(),
            RepairTicket::where('status', 'completed')->count(),
            RepairTicket::where('status', 'delivered')->count(),
            RepairTicket::where('status', 'cancelled')->count(),
        ];

        return [
            'datasets' => [[
                'data'            => $counts,
                'backgroundColor' => [
                    '#94a3b8', // open       – slate
                    '#f59e0b', // diagnostic – amber
                    '#3b82f6', // assigned   – blue
                    '#8b5cf6', // in_progress– violet
                    '#10b981', // completed  – emerald
                    '#06b6d4', // delivered  – cyan
                    '#ef4444', // cancelled  – red
                ],
                'hoverOffset'     => 8,
                'borderWidth'     => 2,
                'borderColor'     => '#ffffff',
            ]],
            'labels' => [
                __('messages.open'),
                __('messages.diagnostic'),
                __('messages.assigned'),
                __('messages.in_progress'),
                __('messages.completed'),
                __('messages.delivered'),
                __('messages.cancelled'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels'   => ['padding' => 20, 'usePointStyle' => true],
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
