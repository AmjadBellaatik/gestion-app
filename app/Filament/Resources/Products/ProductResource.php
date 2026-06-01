<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-cube';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'barcode'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'SKU'                      => $record->sku,
            __('messages.stock')       => $record->quantity,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.products');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('messages.stock_management');
    }

    public static function getModelLabel(): string
    {
        return __('messages.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.products');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_products'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_products'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {
        return ProductForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {
        return ProductInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {
        return ProductsTable::configure(
            $table
        );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListProducts::route('/'),

            'create' =>
                CreateProduct::route('/create'),

            'view' =>
                ViewProduct::route('/{record}'),

            'edit' =>
                EditProduct::route('/{record}/edit'),
        ];
    }
}
