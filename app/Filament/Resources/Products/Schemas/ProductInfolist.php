<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                /*
                |--------------------------------------------------------------
                | Product Details — left column
                |--------------------------------------------------------------
                */
                Section::make(__('messages.product_details'))
                    ->columnSpan(1)
                    ->schema([

                        TextEntry::make('name')
                            ->label(__('messages.name'))
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),

                        TextEntry::make('sku')
                            ->label('SKU')
                            ->placeholder('-')
                            ->icon('heroicon-o-hashtag')
                            ->copyable(),

                        TextEntry::make('barcode')
                            ->label(__('messages.barcode'))
                            ->placeholder('-')
                            ->icon('heroicon-o-bars-3-bottom-left')
                            ->copyable(),

                        TextEntry::make('type')
                            ->label(__('messages.type'))
                            ->badge()
                            ->placeholder('-'),

                        TextEntry::make('status')
                            ->label(__('messages.status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'active'   => 'success',
                                'inactive' => 'gray',
                                default    => 'gray',
                            })
                            ->placeholder('-'),

                        IconEntry::make('has_warranty')
                            ->label(__('messages.has_warranty'))
                            ->boolean(),

                    ])
                    ->columns(2),

                /*
                |--------------------------------------------------------------
                | Pricing & Stock — right column
                |--------------------------------------------------------------
                */
                Section::make(__('messages.pricing_stock'))
                    ->columnSpan(1)
                    ->schema([

                        TextEntry::make('purchase_price')
                            ->label(__('messages.purchase_price'))
                            ->money('MAD')
                            ->weight(FontWeight::Bold)
                            ->visible(fn (): bool => ProductForm::isAdminUser()),

                        TextEntry::make('selling_price')
                            ->label(__('messages.customer_price'))
                            ->money('MAD')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('reseller_price')
                            ->label(__('messages.reseller_price'))
                            ->money('MAD'),

                        TextEntry::make('current_stock')
                            ->label(__('messages.current_stock'))
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->color(fn ($state) => $state <= 0 ? 'danger' : 'success'),

                        TextEntry::make('stock_alert')
                            ->label(__('messages.stock_alert'))
                            ->numeric()
                            ->placeholder('-'),

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
