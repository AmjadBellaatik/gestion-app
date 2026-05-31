<?php

namespace App\Filament\Widgets;

use App\Models\RepairTicket;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TechnicianPerformanceWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                __('messages.completed_repairs'),

                RepairTicket::where(
                    'status',
                    'completed'
                )->count()
            )

                ->description(
                    __('messages.total_completed_repairs')
                )

                ->color('success'),

        ];
    }
}