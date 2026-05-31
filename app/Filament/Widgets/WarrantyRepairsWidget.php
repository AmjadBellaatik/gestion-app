<?php

namespace App\Filament\Widgets;

use App\Models\RepairTicket;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WarrantyRepairsWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                __('messages.warranty_repairs'),
                RepairTicket::where(
                    'is_warranty',
                    true
                )->count()
            )

                ->description(
                    __('messages.repairs_under_warranty')
                )

                ->color('warning'),

        ];
    }
}