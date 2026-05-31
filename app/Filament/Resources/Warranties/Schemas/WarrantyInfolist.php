<?php

namespace App\Filament\Resources\Warranties\Schemas;

use App\Models\Warranty;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarrantyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('messages.warranty_information'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('status')
                            ->label(__('messages.status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'active'    => 'success',
                                'expired'   => 'danger',
                                'claimed'   => 'warning',
                                default     => 'gray',
                            }),
                        TextEntry::make('start_date')
                            ->label(__('messages.start_date'))
                            ->date(),
                        TextEntry::make('end_date')
                            ->label(__('messages.end_date'))
                            ->date(),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('client.display_name')
                            ->label(__('messages.client'))
                            ->placeholder('-'),
                        TextEntry::make('motorcycleUnit.chassis_number')
                            ->label(__('messages.chassis_number'))
                            ->placeholder('-'),
                        TextEntry::make('sale.sale_number')
                            ->label(__('messages.sale'))
                            ->placeholder('-')
                            ->color('primary')
                            ->url(fn (Warranty $record) => $record->sale_id
                                ? \App\Filament\Resources\Sales\SaleResource::getUrl('view', ['record' => $record->sale_id])
                                : null),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('notes')
                            ->label(__('messages.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                ]),

        ]);
    }
}
