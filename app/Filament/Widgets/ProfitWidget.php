<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Sale;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $sales =
            Sale::sum('total');

        $expenses =
            Expense::sum('amount');

        return [

            Stat::make(
                __('messages.profit'),

                number_format(
                    $sales - $expenses,
                    2
                )
            )

                ->description(
                    __('messages.net_profit')
                )

                ->color('success'),

        ];
    }
}