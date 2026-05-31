<?php

namespace App\Filament\Resources\WoocommerceOrderResource\Pages;

use App\Filament\Resources\WoocommerceOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListWoocommerceOrders extends ListRecords
{
    protected static string $resource = WoocommerceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
