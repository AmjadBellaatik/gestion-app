<?php

namespace App\Filament\Resources\MotorcycleModels;

use App\Filament\Resources\MotorcycleModels\Pages\CreateMotorcycleModel;
use App\Filament\Resources\MotorcycleModels\Pages\EditMotorcycleModel;
use App\Filament\Resources\MotorcycleModels\Pages\ListMotorcycleModels;
use App\Filament\Resources\MotorcycleModels\Pages\ViewMotorcycleModel;

use App\Filament\Resources\MotorcycleModels\Schemas\MotorcycleModelForm;
use App\Filament\Resources\MotorcycleModels\Schemas\MotorcycleModelInfolist;

use App\Filament\Resources\MotorcycleModels\Tables\MotorcycleModelsTable;

use App\Models\MotorcycleModel;

use BackedEnum;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Tables\Table;

class MotorcycleModelResource extends Resource
{
    protected static ?string $model =
        MotorcycleModel::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-squares-2x2';

    protected static ?int $navigationSort =
        1;

    protected static ?string $recordTitleAttribute =
        'modele';

    public static function getNavigationLabel(): string
    {
        return __('messages.motorcycle_models');
    }

    public static function getModelLabel(): string
    {
        return __('messages.motorcycle_model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.motorcycle_models');
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

        return MotorcycleModelForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return MotorcycleModelsTable::configure(
            $table
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return MotorcycleModelInfolist::configure(
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
                ListMotorcycleModels::route('/'),

            'create' =>
                CreateMotorcycleModel::route('/create'),

            'view' =>
                ViewMotorcycleModel::route('/{record}'),

            'edit' =>
                EditMotorcycleModel::route('/{record}/edit'),

        ];
    }
}
