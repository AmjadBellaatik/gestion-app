<?php

namespace App\Filament\Widgets;

use App\Models\Product;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockValuationWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                __('messages.stock_valuation'),

                number_format(

                    Product::sum(
                        'purchase_price'
                    ),

                    2
                )
            )

                ->description(
                    __('messages.total_inventory_value')
                )

                ->color('info'),

        ];
    }
}