<?php

namespace App\Filament\Resources\MotorcycleUnits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MotorcycleUnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.motorcycle_unit'))
                    ->schema([
                        TextEntry::make('motorcycleModel.type')
                            ->label(__('messages.type'))
                            ->placeholder('-'),

                        TextEntry::make('chassis_number')
                            ->label(__('messages.chassis_number'))
                            ->placeholder('-'),

                        TextEntry::make('engine_number')
                            ->label(__('messages.engine_number'))
                            ->placeholder('-'),

                        TextEntry::make('color')
                            ->label(__('messages.color'))
                            ->placeholder('-'),

                        TextEntry::make('boite_vitesse')
                            ->label(__('messages.boite_vitesse'))
                            ->placeholder('-'),

                        TextEntry::make('status')
                            ->label(__('messages.status'))
                            ->badge()
                            ->formatStateUsing(
                                fn ($state) => match ($state) {
                                    'in_stock' => __('messages.in_stock'),
                                    'reserved' => __('messages.reserved'),
                                    'sold' => __('messages.sold'),
                                    'repair' => __('messages.repair'),
                                    default => $state,
                                }
                            )
                            ->color(fn ($state) => match ($state) {
                                'in_stock' => 'success',
                                'reserved' => 'warning',
                                'sold' => 'danger',
                                'repair' => 'info',
                                default => 'gray',
                            }),

                        TextEntry::make('warehouse.name')
                            ->label(__('messages.warehouse'))
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label(__('messages.created_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}
