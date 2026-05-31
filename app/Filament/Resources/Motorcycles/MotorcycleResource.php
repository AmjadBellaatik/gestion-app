<?php

namespace App\Filament\Resources\Motorcycles;

use App\Filament\Resources\Motorcycles\Pages\CreateMotorcycle;
use App\Filament\Resources\Motorcycles\Pages\EditMotorcycle;
use App\Filament\Resources\Motorcycles\Pages\ListMotorcycles;
use App\Filament\Resources\Motorcycles\Pages\ViewMotorcycle;

use App\Filament\Resources\Motorcycles\Schemas\MotorcycleForm;
use App\Filament\Resources\Motorcycles\Schemas\MotorcycleInfolist;
use App\Filament\Resources\Motorcycles\Tables\MotorcyclesTable;

use App\Models\MotorcycleUnit;

use BackedEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Support\Icons\Heroicon;

class MotorcycleResource extends Resource
{
    protected static bool $shouldRegisterNavigation =
        false;

    protected static ?string $model =
        MotorcycleUnit::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedTruck;

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

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('messages.motorcycles');
    }

    public static function getModelLabel(): string
    {
        return __('messages.motorcycle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.motorcycles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.inventory');
    }

    public static function form(
        Schema $schema
    ): Schema {

        return MotorcycleForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return MotorcycleInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return MotorcyclesTable::configure(
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
                ListMotorcycles::route('/'),

            'create' =>
                CreateMotorcycle::route('/create'),

            'view' =>
                ViewMotorcycle::route('/{record}'),

            'edit' =>
                EditMotorcycle::route('/{record}/edit'),

        ];
    }
}
