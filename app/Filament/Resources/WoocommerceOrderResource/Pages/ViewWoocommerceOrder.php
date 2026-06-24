<?php

namespace App\Filament\Resources\WoocommerceOrderResource\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\WoocommerceOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewWoocommerceOrder extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = WoocommerceOrderResource::class;

    public function getTitle(): string
    {
        return $this->record->wc_order_number ?? __('messages.woocommerce_order');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
