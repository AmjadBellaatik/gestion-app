<?php

namespace App\Filament\Resources\MotorcycleUnits;

use App\Filament\Resources\MotorcycleUnits\Pages\CreateMotorcycleUnit;
use App\Filament\Resources\MotorcycleUnits\Pages\EditMotorcycleUnit;
use App\Filament\Resources\MotorcycleUnits\Pages\ListMotorcycleUnits;
use App\Filament\Resources\MotorcycleUnits\Pages\ViewMotorcycleUnit;

use App\Filament\Resources\MotorcycleUnits\Schemas\MotorcycleUnitForm;
use App\Filament\Resources\MotorcycleUnits\Schemas\MotorcycleUnitInfolist;

use App\Filament\Resources\MotorcycleUnits\Tables\MotorcycleUnitsTable;

use App\Models\MotorcycleUnit;

use BackedEnum;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Tables\Table;

class MotorcycleUnitResource extends Resource
{
    protected static ?string $model =
        MotorcycleUnit::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-truck';

    protected static ?int $navigationSort =
        2;

    protected static ?string $recordTitleAttribute =
        'chassis_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['chassis_number', 'fabrication_number'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            __('messages.motorcycle_model') => $record->motorcycleModel?->marque . ' ' . $record->motorcycleModel?->modele,
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.motorcycle_units');
    }

    public static function getModelLabel(): string
    {
        return __('messages.motorcycle_unit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.motorcycle_units');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.motorcycles');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_motorcycles'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_motorcycles'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return MotorcycleUnitForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return MotorcycleUnitsTable::configure(
            $table
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return MotorcycleUnitInfolist::configure(
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
                ListMotorcycleUnits::route('/'),

            'create' =>
                CreateMotorcycleUnit::route('/create'),

            'view' =>
                ViewMotorcycleUnit::route('/{record}'),

            'edit' =>
                EditMotorcycleUnit::route('/{record}/edit'),

        ];
    }
}
