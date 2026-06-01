<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\CreateStockMovement;
use App\Filament\Resources\StockMovements\Pages\EditStockMovement;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\StockMovements\Pages\ViewStockMovement;

use App\Filament\Resources\StockMovements\Schemas\StockMovementForm;
use App\Filament\Resources\StockMovements\Schemas\StockMovementInfolist;
use App\Filament\Resources\StockMovements\Tables\StockMovementsTable;

use App\Models\StockMovement;

use BackedEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Support\Icons\Heroicon;

class StockMovementResource extends Resource
{
    protected static ?string $model =
        StockMovement::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute =
        'movement_type';

    public static function getNavigationLabel(): string
    {
        return __('messages.stock_movements');
    }

    public static function getModelLabel(): string
    {
        return __('messages.stock_movement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.stock_movements');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.stock_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_stock'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_stock'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return StockMovementForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return StockMovementInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return StockMovementsTable::configure(
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
                ListStockMovements::route('/'),

            'create' =>
                CreateStockMovement::route('/create'),

            'view' =>
                ViewStockMovement::route('/{record}'),

            'edit' =>
                EditStockMovement::route('/{record}/edit'),

        ];
    }
}
