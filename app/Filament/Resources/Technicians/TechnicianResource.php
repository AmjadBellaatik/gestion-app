<?php

namespace App\Filament\Resources\Technicians;

use App\Filament\Resources\Technicians\Pages\CreateTechnician;
use App\Filament\Resources\Technicians\Pages\EditTechnician;
use App\Filament\Resources\Technicians\Pages\ListTechnicians;
use App\Filament\Resources\Technicians\Pages\ViewTechnician;

use App\Models\Technician;

use Filament\Forms;

use Filament\Resources\Resource;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables;
use Filament\Tables\Table;

class TechnicianResource extends Resource
{
    protected static ?string $model =
        Technician::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-wrench';

    protected static ?int $navigationSort =
        2;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function getNavigationLabel(): string
    {
        return __('messages.technicians');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return __('messages.workshop');
    }

    public static function getModelLabel(): string
    {
        return __('messages.technician');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.technicians');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_technicians'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_technicians'
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

                        Forms\Components\TextInput::make(
                            'name'
                        )

                            ->label(
                                __('messages.name')
                            )

                            ->required(),

                        Forms\Components\TextInput::make(
                            'phone'
                        )

                            ->label(
                                __('messages.phone')
                            ),

                        Forms\Components\TextInput::make(
                            'speciality'
                        )

                            ->label(
                                __('messages.speciality')
                            ),

                        Forms\Components\Toggle::make(
                            'is_active'
                        )

                            ->label(
                                __('messages.is_active')
                            )

                            ->default(true),

                    ])

                    ->columns(2),

            ]);
    }

    public static function table(
        Table $table
    ): Table {

        return $table

            ->columns([

                Tables\Columns\TextColumn::make(
                    'name'
                )

                    ->label(
                        __('messages.name')
                    )

                    ->searchable()

                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'phone'
                )

                    ->label(
                        __('messages.phone')
                    ),

                Tables\Columns\TextColumn::make(
                    'speciality'
                )

                    ->label(
                        __('messages.speciality')
                    )

                    ->badge(),

                Tables\Columns\IconColumn::make(
                    'is_active'
                )

                    ->label(
                        __('messages.is_active')
                    )

                    ->boolean(),

                Tables\Columns\TextColumn::make(
                    'created_at'
                )

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable(),

            ])

            ->filters([

                Tables\Filters\TernaryFilter::make(
                    'is_active'
                )

                    ->label(
                        __('messages.is_active')
                    ),

            ])

            ->recordUrl(

                fn ($record) => static::getUrl(
                    'edit',
                    ['record' => $record]
                )

            );
    }

    public static function getPages(): array
    {
        return [

            'index' =>
                ListTechnicians::route('/'),

            'create' =>
                CreateTechnician::route('/create'),

            'view' =>
                ViewTechnician::route('/{record}'),

            'edit' =>
                EditTechnician::route('/{record}/edit'),

        ];
    }
}