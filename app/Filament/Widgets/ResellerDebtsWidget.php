<?php

namespace App\Filament\Widgets;

use App\Models\Reseller;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResellerDebtsWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                __('messages.reseller_debts'),
                number_format(

                    Reseller::sum(
                        'current_debt'
                    ),

                    2
                )
            )

                ->description(
                    __('messages.total_reseller_unpaid')
                )

                ->color('danger'),

        ];
    }
}