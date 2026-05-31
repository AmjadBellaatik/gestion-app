<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company_id')
                    ->numeric(),
                TextEntry::make('name'),
                TextEntry::make('sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextEntry::make('purchase_price')
                    ->label(__('messages.purchase_price'))
                    ->money('MAD')
                    ->visible(fn (): bool => ProductForm::isAdminUser()),
                TextEntry::make('selling_price')
                    ->label(__('messages.customer_price'))
                    ->money('MAD'),
                TextEntry::make('reseller_price')
                    ->label(__('messages.reseller_price'))
                    ->money('MAD'),
                TextEntry::make('current_stock')
                    ->label(__('messages.stock'))
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
