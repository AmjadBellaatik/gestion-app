<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Stock\Support\StockActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('add_stock')
                ->label(__('messages.add_stock'))
                ->icon('heroicon-o-plus')
                ->form(fn (): array => StockActions::productMovementForm($this->record->id))
                ->action(fn (array $data) => StockActions::addProductStock($data)),

            Action::make('adjust_stock')
                ->label(__('messages.adjust_stock'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (): bool => StockActions::canAdjust())
                ->form(fn (): array => StockActions::productAdjustmentForm($this->record->id))
                ->action(fn (array $data) => StockActions::adjustProductStock($data)),

            DeleteAction::make(),
        ];
    }
}
