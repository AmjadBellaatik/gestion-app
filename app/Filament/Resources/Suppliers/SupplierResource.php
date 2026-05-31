<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Resources\Suppliers\Schemas\SupplierInfolist;
use App\Filament\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Supplier;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-truck';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function getNavigationLabel(): string
    {
        return __('messages.suppliers');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('messages.stock_management');
    }

    public static function getModelLabel(): string
    {
        return __('messages.supplier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.suppliers');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_suppliers'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_suppliers'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {
        return SupplierForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {
        return SupplierInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {
        return SuppliersTable::configure(
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
                ListSuppliers::route('/'),

            'create' =>
                CreateSupplier::route('/create'),

            'view' =>
                ViewSupplier::route('/{record}'),

            'edit' =>
                EditSupplier::route('/{record}/edit'),
        ];
    }
}
