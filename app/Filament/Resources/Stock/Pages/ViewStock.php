<?php

namespace App\Filament\Resources\Stock\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Stock\StockResource;
use App\Filament\Resources\Stock\Support\StockActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewStock extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_stock')
                ->label(__('messages.add_stock'))
                ->icon('heroicon-o-plus')
                ->form(fn (): array => $this->record->item_kind === 'motorcycle_model'
                    ? StockActions::motorcycleUnitForm((int) $this->record->motorcycle_model_id)
                    : StockActions::productMovementForm((int) $this->record->product_id))
                ->action(function (array $data): void {
                    if ($this->record->item_kind === 'motorcycle_model') {
                        StockActions::addMotorcycleStock($data);

                        return;
                    }

                    StockActions::addProductStock($data);
                }),

            Action::make('adjust_stock')
                ->label(__('messages.adjust_stock'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (): bool => $this->record->item_kind === 'product' && StockActions::canAdjust())
                ->form(fn () => StockActions::productAdjustmentForm((int) $this->record->product_id))
                ->action(fn (array $data) => StockActions::adjustProductStock($data)),
        ];
    }
}
