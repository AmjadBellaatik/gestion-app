<?php

namespace App\Filament\Resources\Warranties;

use App\Filament\Resources\Warranties\Pages\CreateWarranty;
use App\Filament\Resources\Warranties\Pages\EditWarranty;
use App\Filament\Resources\Warranties\Pages\ListWarranties;
use App\Filament\Resources\Warranties\Pages\ViewWarranty;

use App\Models\Client;
use App\Models\MotorcycleUnit;
use App\Models\Product;
use App\Models\Warranty;

use BackedEnum;

use App\Filament\Resources\Warranties\Tables\WarrantiesTable;

use Filament\Forms;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

use Filament\Tables\Table;

class WarrantyResource extends Resource
{
    protected static ?string $model =
        Warranty::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-shield-check';

    protected static ?int $navigationSort =
        3;

    protected static ?string $recordTitleAttribute =
        'notes';

    public static function getNavigationLabel(): string
    {
        return __('messages.warranties');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.workshop');
    }

    public static function getModelLabel(): string
    {
        return __('messages.warranty');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.warranties');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_warranty') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_warranty') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::isAdminUser();
    }

    public static function canEdit($record): bool
    {
        return static::isAdminUser();
    }

    public static function canDelete($record): bool
    {
        return static::isAdminUser();
    }

    public static function isAdminUser(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('messages.general_information'))
                ->schema([

                    Grid::make(2)->schema([

                        Forms\Components\Select::make('client_id')
                            ->label(__('messages.client'))
                            ->options(fn () => Client::query()
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (Client $c) => [$c->id => $c->display_name])
                                ->toArray())
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('sale_id')
                            ->label(__('messages.sale'))
                            ->relationship('sale', 'sale_number')
                            ->searchable()
                            ->preload(),

                    ]),

                    Grid::make(2)->schema([

                        Forms\Components\Select::make('motorcycle_unit_id')
                            ->label(__('messages.motorcycle_unit'))
                            ->options(fn () => MotorcycleUnit::query()
                                ->with('motorcycleModel')
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (MotorcycleUnit $u) => [
                                    $u->id => trim(($u->motorcycleModel?->modele ?? __('messages.motorcycle')) . ' — ' . $u->chassis_number),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.none')),

                        Forms\Components\Select::make('product_id')
                            ->label(__('messages.product'))
                            ->options(fn () => Product::query()
                                ->whereNotNull('name')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.none')),

                    ]),

                    Grid::make(2)->schema([

                        Forms\Components\DatePicker::make('start_date')
                            ->label(__('messages.start_date'))
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label(__('messages.end_date'))
                            ->required()
                            ->afterOrEqual('start_date'),

                    ]),

                    Forms\Components\TextInput::make('warranty_kilometers')
                        ->label(__('messages.warranty_distance'))
                        ->numeric()
                        ->minValue(1)
                        ->suffix('KM'),

                ]),

            Section::make(__('messages.notes'))
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label(__('messages.notes'))
                        ->rows(3),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return WarrantiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [

            'index' =>
                ListWarranties::route('/'),

            'create' =>
                CreateWarranty::route('/create'),

            'view' =>
                ViewWarranty::route('/{record}'),

            'edit' =>
                EditWarranty::route('/{record}/edit'),

        ];
    }
}