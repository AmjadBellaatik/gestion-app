<?php

namespace App\Filament\Resources\PurchaseItems;

use App\Filament\Resources\PurchaseItems\Pages\CreatePurchaseItem;
use App\Filament\Resources\PurchaseItems\Pages\EditPurchaseItem;
use App\Filament\Resources\PurchaseItems\Pages\ListPurchaseItems;
use App\Filament\Resources\PurchaseItems\Pages\ViewPurchaseItem;

use App\Filament\Resources\PurchaseItems\Schemas\PurchaseItemForm;
use App\Filament\Resources\PurchaseItems\Schemas\PurchaseItemInfolist;

use App\Filament\Resources\PurchaseItems\Tables\PurchaseItemsTable;

use App\Models\PurchaseOrderItem;

use BackedEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseItemResource extends Resource
{
    protected static ?string $model =
        PurchaseOrderItem::class;

    protected static bool $shouldRegisterNavigation =
        false;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute =
        'id';

    public static function form(
        Schema $schema
    ): Schema {

        return PurchaseItemForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return PurchaseItemInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return PurchaseItemsTable::configure(
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

            'index' => ListPurchaseItems::route('/'),

            'create' => CreatePurchaseItem::route('/create'),

            'view' => ViewPurchaseItem::route('/{record}'),

            'edit' => EditPurchaseItem::route('/{record}/edit'),

        ];
    }
}