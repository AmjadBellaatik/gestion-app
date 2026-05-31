<?php

namespace App\Filament\Resources\Stock;

use App\Filament\Resources\Stock\Pages\ListStock;
use App\Filament\Resources\Stock\Pages\ViewStock;
use App\Models\StockItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StockResource extends Resource
{
    protected static ?string $model = StockItem::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'stock';

    public static function getNavigationLabel(): string
    {
        return __('messages.stock');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.stock_management');
    }

    public static function getModelLabel(): string
    {
        return __('messages.stock');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.stock');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_stock') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_stock') ?? false;
    }

    public static function table(Table $table): Table
    {
        return Tables\StockTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\StockInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStock::route('/'),
            'view' => ViewStock::route('/{record}'),
        ];
    }
}
