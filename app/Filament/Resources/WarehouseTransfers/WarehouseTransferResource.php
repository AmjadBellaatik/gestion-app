<?php

namespace App\Filament\Resources\WarehouseTransfers;

use App\Filament\Resources\WarehouseTransfers\Pages\CreateWarehouseTransfer;
use App\Filament\Resources\WarehouseTransfers\Pages\EditWarehouseTransfer;
use App\Filament\Resources\WarehouseTransfers\Pages\ListWarehouseTransfers;
use App\Filament\Resources\WarehouseTransfers\Pages\ViewWarehouseTransfer;

use App\Filament\Resources\WarehouseTransfers\Schemas\WarehouseTransferForm;
use App\Filament\Resources\WarehouseTransfers\Schemas\WarehouseTransferInfolist;

use App\Filament\Resources\WarehouseTransfers\Tables\WarehouseTransfersTable;

use App\Models\StockTransfer;

use BackedEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarehouseTransferResource extends Resource
{
    protected static bool $shouldRegisterNavigation =
        false;

    protected static ?string $model =
        StockTransfer::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute =
        'reference';

    public static function form(
        Schema $schema
    ): Schema {

        return WarehouseTransferForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return WarehouseTransferInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return WarehouseTransfersTable::configure(
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

            'index' => ListWarehouseTransfers::route('/'),

            'create' => CreateWarehouseTransfer::route('/create'),

            'view' => ViewWarehouseTransfer::route('/{record}'),

            'edit' => EditWarehouseTransfer::route('/{record}/edit'),

        ];
    }
}