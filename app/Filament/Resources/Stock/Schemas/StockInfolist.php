<?php

namespace App\Filament\Resources\Stock\Schemas;

use App\Models\StockItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.stock'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('messages.product')),
                        TextEntry::make('item_kind')
                            ->label(__('messages.type'))
                            ->formatStateUsing(fn ($state) => $state === 'motorcycle_model' ? __('messages.motorcycle_model') : __('messages.product')),
                        TextEntry::make('type')
                            ->label(__('messages.category'))
                            ->placeholder('-'),
                        TextEntry::make('stock_alert')
                            ->label(__('messages.stock_alert'))
                            ->numeric(),
                        TextEntry::make('live_quantity')
                            ->label(__('messages.live_quantity'))
                            ->state(fn (StockItem $record): float => $record->live_quantity)
                            ->color(fn (StockItem $record): string => $record->is_low_stock ? 'danger' : 'gray'),
                    ])
                    ->columns(2),
            ]);
    }
}
