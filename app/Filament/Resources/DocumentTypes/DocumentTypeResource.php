<?php

namespace App\Filament\Resources\DocumentTypes;

use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\EditDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Filament\Resources\DocumentTypes\Pages\ViewDocumentType;

use App\Models\DocumentType;

use Filament\Forms;

use Filament\Resources\Resource;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables;
use Filament\Tables\Table;

class DocumentTypeResource extends Resource
{
    protected static ?string $model =
        DocumentType::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort =
        2;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function getNavigationLabel(): string
    {
        return __('messages.document_types');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return __('messages.settings');
    }

    public static function getModelLabel(): string
    {
        return __('messages.document_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.document_types');
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
                            'code'
                        )

                            ->label(
                                __('messages.code')
                            )

                            ->required(),

                        Forms\Components\TextInput::make(
                            'prefix'
                        )

                            ->label(
                                __('messages.prefix')
                            )

                            ->required(),

                        Forms\Components\Toggle::make(
                            'affects_stock'
                        )

                            ->label(
                                __('messages.affects_stock')
                            )

                            ->default(false),

                        Forms\Components\Toggle::make(
                            'affects_accounting'
                        )

                            ->label(
                                __('messages.affects_accounting')
                            )

                            ->default(false),

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
                    'code'
                )

                    ->label(
                        __('messages.code')
                    )

                    ->badge()

                    ->searchable(),

                Tables\Columns\TextColumn::make(
                    'prefix'
                )

                    ->label(
                        __('messages.prefix')
                    ),

                Tables\Columns\IconColumn::make(
                    'affects_stock'
                )

                    ->label(
                        __('messages.affects_stock')
                    )

                    ->boolean(),

                Tables\Columns\IconColumn::make(
                    'affects_accounting'
                )

                    ->label(
                        __('messages.affects_accounting')
                    )

                    ->boolean(),

                Tables\Columns\IconColumn::make(
                    'is_active'
                )

                    ->label(
                        __('messages.is_active')
                    )

                    ->boolean(),

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
                ListDocumentTypes::route('/'),

            'create' =>
                CreateDocumentType::route('/create'),

            'view' =>
                ViewDocumentType::route('/{record}'),

            'edit' =>
                EditDocumentType::route('/{record}/edit'),

        ];
    }
}