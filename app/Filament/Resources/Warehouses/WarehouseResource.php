<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Pages\ViewWarehouse;

use App\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\Warehouses\Schemas\WarehouseInfolist;

use App\Filament\Resources\Warehouses\Tables\WarehousesTable;

use App\Models\Warehouse;

use BackedEnum;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model =
        Warehouse::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-building-storefront';

    protected static ?int $navigationSort =
        4;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function getNavigationLabel(): string
    {
        return __('messages.warehouses');
    }

    public static function getModelLabel(): string
    {
        return __('messages.warehouse');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.warehouses');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.stock_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_warehouses'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_warehouses'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return WarehouseForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return WarehousesTable::configure(
            $table
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return WarehouseInfolist::configure(
            $schema
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
                ListWarehouses::route('/'),

            'create' =>
                CreateWarehouse::route('/create'),

            'view' =>
                ViewWarehouse::route('/{record}'),

            'edit' =>
                EditWarehouse::route('/{record}/edit'),

        ];
    }
}
