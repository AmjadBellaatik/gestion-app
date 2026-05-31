<?php

namespace App\Filament\Resources\Stock\Pages;

use App\Filament\Resources\Stock\StockResource;
use App\Filament\Resources\Stock\Support\StockActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListStock extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_stock')
                ->label(__('messages.add_stock'))
                ->icon('heroicon-o-plus')
                ->form(StockActions::productMovementForm())
                ->action(fn (array $data) => StockActions::addProductStock($data)),
        ];
    }
}
