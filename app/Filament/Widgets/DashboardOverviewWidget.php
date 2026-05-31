<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverviewWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(

                __('messages.repair_revenue'),

                '0.00 MAD'

            )

                ->description(
                    __('messages.repair_revenue')
                ),

            Stat::make(

                __('messages.warranty_repairs'),

                '0'

            )

                ->description(
                    __('messages.warranty_repairs')
                ),

            Stat::make(

                __('messages.reseller_debts'),

                '0.00 MAD'

            )

                ->description(
                    __('messages.reseller_debts')
                ),

            Stat::make(

                __('messages.unpaid_invoices'),

                '0.00 MAD'

            )

                ->description(
                    __('messages.unpaid_invoices')
                ),

            Stat::make(

                __('messages.low_stock'),

                __('messages.stock_erp')

            )

                ->description(
                    __('messages.calculated_from_movements')
                ),

            Stat::make(

                __('messages.technician_performance'),

                __('messages.zero_repairs')

            )

                ->description(
                    __('messages.technician_performance')
                ),

        ];
    }
}
