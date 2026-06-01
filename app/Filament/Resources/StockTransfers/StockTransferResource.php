<?php

namespace App\Filament\Resources\StockTransfers;

use App\Filament\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\EditStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Filament\Resources\StockTransfers\Pages\ViewStockTransfer;

use App\Filament\Resources\StockTransfers\Schemas\StockTransferForm;

use App\Filament\Resources\StockTransfers\Tables\StockTransfersTable;

use App\Models\StockTransfer;

use BackedEnum;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Tables\Table;

class StockTransferResource extends Resource
{
    protected static ?string $model =
        StockTransfer::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-arrow-path';

    protected static ?int $navigationSort =
        50;

    protected static ?string $recordTitleAttribute =
        'reference';

    public static function getNavigationLabel(): string
    {
        return __('messages.stock_transfers');
    }

    public static function getModelLabel(): string
    {
        return __('messages.stock_transfer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.stock_transfers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.stock_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_stock_transfers')
            || auth()->user()?->can('transfer_stock')
            || auth()->user()?->can('manage_stock')
            || false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_stock_transfers')
            || auth()->user()?->can('transfer_stock')
            || auth()->user()?->can('manage_stock')
            || false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return StockTransferForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return StockTransfersTable::configure(
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
                ListStockTransfers::route('/'),

            'create' =>
                CreateStockTransfer::route('/create'),

            'view' =>
                ViewStockTransfer::route('/{record}'),

            'edit' =>
                EditStockTransfer::route('/{record}/edit'),

        ];
    }
}
