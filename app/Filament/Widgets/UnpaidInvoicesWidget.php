<?php

namespace App\Filament\Widgets;

use App\Models\Sale;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UnpaidInvoicesWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                __('messages.unpaid_invoices'),

                Sale::where(
                    'payment_status',
                    'unpaid'
                )->count()
            )

                ->description(
                    __('messages.pending_customer_payments')
                )

                ->color('danger'),

        ];
    }
}