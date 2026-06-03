<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                /*
                |--------------------------------------------------------------
                | Contact Information — left column
                |--------------------------------------------------------------
                */
                Section::make(__('messages.supplier_information'))
                    ->columnSpan(1)
                    ->schema([

                        TextEntry::make('name')
                            ->label(__('messages.name'))
                            ->weight(\Filament\Support\Enums\FontWeight::Bold),

                        TextEntry::make('phone')
                            ->label(__('messages.phone'))
                            ->placeholder('-')
                            ->icon('heroicon-o-phone'),

                        TextEntry::make('email')
                            ->label(__('messages.email'))
                            ->placeholder('-')
                            ->icon('heroicon-o-envelope'),

                        TextEntry::make('address')
                            ->label(__('messages.address'))
                            ->placeholder('-')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpanFull(),

                    ])
                    ->columns(2),

                /*
                |--------------------------------------------------------------
                | Financial Summary — right column
                |--------------------------------------------------------------
                */
                Section::make(__('messages.financial_summary'))
                    ->columnSpan(1)
                    ->schema([

                        TextEntry::make('balance')
                            ->label(__('messages.balance'))
                            ->money('MAD')
                            ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold),

                        TextEntry::make('total_purchases')
                            ->label(__('messages.total_purchases'))
                            ->money('MAD')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold),

                        TextEntry::make('created_at')
                            ->label(__('messages.created_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label(__('messages.updated_at'))
                            ->dateTime()
                            ->placeholder('-'),

                    ])
                    ->columns(2),

            ]);
    }
}
