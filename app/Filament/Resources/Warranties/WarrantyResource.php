<?php

namespace App\Filament\Resources\Warranties;

use App\Filament\Resources\Warranties\Pages\CreateWarranty;
use App\Filament\Resources\Warranties\Pages\EditWarranty;
use App\Filament\Resources\Warranties\Pages\ListWarranties;
use App\Filament\Resources\Warranties\Pages\ViewWarranty;

use App\Models\Warranty;

use BackedEnum;

use App\Filament\Resources\Warranties\Tables\WarrantiesTable;

use Filament\Forms;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

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
        'status';

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
        return auth()->user()?->can(
            'manage_warranty'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_warranty'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return $schema

            ->components([

                Section::make(
                    __('messages.general_information')
                )

                    ->schema([

                        Forms\Components\Select::make(
                            'client_id'
                        )

                            ->label(
                                __('messages.client')
                            )

                            ->relationship(
                                'client',
                                'first_name'
                            )

                            ->searchable()

                            ->preload(),

                        Forms\Components\Select::make(
                            'motorcycle_id'
                        )

                            ->label(
                                __('messages.motorcycle_unit')
                            )

                            ->relationship(
                                'motorcycle',
                                'name'
                            )

                            ->searchable()

                            ->preload(),

                        Forms\Components\Select::make(
                            'sale_id'
                        )

                            ->label(
                                __('messages.sale')
                            )

                            ->relationship(
                                'sale',
                                'sale_number'
                            )

                            ->searchable()

                            ->preload(),

                        Forms\Components\DatePicker::make(
                            'start_date'
                        )

                            ->label(
                                __('messages.start_date')
                            )

                            ->required(),

                        Forms\Components\DatePicker::make(
                            'end_date'
                        )

                            ->label(
                                __('messages.end_date')
                            )

                            ->required(),

                        Forms\Components\Select::make(
                            'status'
                        )

                            ->label(
                                __('messages.status')
                            )

                            ->options([

                                'active' =>
                                    __('messages.active'),

                                'expired' =>
                                    __('messages.expired'),

                                'cancelled' =>
                                    __('messages.cancelled'),

                            ])

                            ->required(),

                    ])

                    ->columns(2),

                Section::make(
                    __('messages.notes')
                )

                    ->schema([

                        Forms\Components\Textarea::make(
                            'notes'
                        )

                            ->label(
                                __('messages.notes')
                            ),

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return WarrantiesTable::configure($table)
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]));
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