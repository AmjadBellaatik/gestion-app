<?php

namespace App\Filament\Resources\Purchases;

use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Purchases\Pages\ViewPurchase;

use App\Filament\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Resources\Purchases\Schemas\PurchaseInfolist;

use App\Filament\Resources\Purchases\Tables\PurchasesTable;

use App\Models\PurchaseOrder;

use BackedEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model =
        PurchaseOrder::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-clipboard-document';

    protected static ?int $navigationSort =
        5;

    protected static ?string $recordTitleAttribute =
        'reference';

    public static function getNavigationLabel(): string
    {
        return __('messages.purchase_orders');
    }

    public static function getModelLabel(): string
    {
        return __('messages.purchase_order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.purchase_orders');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.stock_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return PurchaseForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return PurchaseInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return PurchasesTable::configure(
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
                ListPurchases::route('/'),

            'create' =>
                CreatePurchase::route('/create'),

            'view' =>
                ViewPurchase::route('/{record}'),

            'edit' =>
                EditPurchase::route('/{record}/edit'),

        ];
    }
}
