<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                __('messages.low_stock'),
                __('messages.stock_movement_erp')
            )

                ->description(
                    __('messages.calculated_from_stock_movements')
                )

                ->color('warning'),

        ];
    }
}
