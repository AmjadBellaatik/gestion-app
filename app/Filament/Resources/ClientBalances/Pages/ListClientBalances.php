<?php

namespace App\Filament\Resources\ClientBalances\Pages;

use App\Filament\Resources\ClientBalances\ClientBalanceResource;
use App\Filament\Resources\ClientBalances\Widgets\ClientBalanceStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListClientBalances extends ListRecords
{
    protected static string $resource = ClientBalanceResource::class;

    /** Accounting summary widgets shown above the table. */
    protected function getHeaderWidgets(): array
    {
        return [
            ClientBalanceStatsWidget::class,
        ];
    }
}
