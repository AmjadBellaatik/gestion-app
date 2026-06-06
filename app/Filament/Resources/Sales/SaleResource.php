<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\Pages\CreateSale;
use App\Filament\Resources\Sales\Pages\EditSale;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Filament\Resources\Sales\Pages\ViewSale;

use App\Filament\Resources\Sales\Schemas\SaleForm;
use App\Filament\Resources\Sales\Schemas\SaleInfolist;
use App\Filament\Resources\Sales\Tables\SalesTable;

use App\Models\Sale;

use BackedEnum;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model =
        Sale::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-shopping-cart';

    protected static ?int $navigationSort =
        3;

    protected static ?string $recordTitleAttribute =
        'sale_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['sale_number'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            __('messages.client')         => $record->client?->display_name,
            __('messages.total')          => $record->total ? 'MAD ' . number_format($record->total, 2) : null,
            __('messages.payment_status') => $record->payment_status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public static function getNavigationLabel(): string
    {
        return __('messages.sales');
    }

    public static function getModelLabel(): string
    {
        return __('messages.sale');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.sales');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.commercial');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_sales'
        ) ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_sales'
        ) ?? false;
    }

    public static function canCreate(): bool
    {
        return self::isAdminUser()
            && (auth()->user()?->can('create_sales') ?? false);
    }

    public static function canEdit(
        $record
    ): bool {

        return auth()->user()?->can(
            'edit_sales'
        ) ?? false;
    }

    public static function canDelete(
        $record
    ): bool {

        return self::isAdminUser()
            && (auth()->user()?->can('delete_sales') ?? false);
    }

    public static function isAdminUser(): bool
    {
        return auth()->user()?->hasAnyRole([
            'Admin',
            'Super Admin',
        ]) ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public static function form(
        Schema $schema
    ): Schema {

        return SaleForm::configure(
            $schema
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Infolist
    |--------------------------------------------------------------------------
    */

    public static function infolist(
        Schema $schema
    ): Schema {

        return SaleInfolist::configure(
            $schema
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public static function table(
        Table $table
    ): Table {

        return SalesTable::configure(
            $table
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Sales\RelationManagers\SaleDateLogsRelationManager::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [

            'index' =>
                ListSales::route('/'),

            'create' =>
                CreateSale::route('/create'),

            'view' =>
                ViewSale::route('/{record}'),

            'edit' =>
                EditSale::route('/{record}/edit'),

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    */

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::

            getRecordRouteBindingEloquentQuery()

            ->withoutGlobalScopes([

                SoftDeletingScope::class,

            ]);
    }
}
