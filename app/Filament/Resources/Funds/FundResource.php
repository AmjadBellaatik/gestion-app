<?php

namespace App\Filament\Resources\Funds;

use App\Filament\Resources\Funds\Pages\CreateFund;
use App\Filament\Resources\Funds\Pages\EditFund;
use App\Filament\Resources\Funds\Pages\ListFunds;
use App\Filament\Resources\Funds\Pages\ViewFund;

use App\Models\Fund;

use BackedEnum;

use Filament\Forms;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

use Filament\Tables;
use Filament\Tables\Table;

class FundResource extends Resource
{
    protected static ?string $model =
        Fund::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-building-library';

    protected static ?int $navigationSort =
        3;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function getNavigationLabel(): string
    {
        return __('messages.cash_registers');
    }

    public static function getModelLabel(): string
    {
        return __('messages.cash_register');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.cash_registers');
    }

    public static function getNavigationGroup(): string
    {
        return __('messages.accounting');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_funds'
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

                        Forms\Components\Select::make(
                            'type'
                        )

                            ->label(
                                __('messages.type')
                            )

                            ->options([

                                'cash' =>
                                    __('messages.cash'),

                                'bank' =>
                                    __('messages.bank'),

                                'mobile' =>
                                    __('messages.mobile'),

                            ])

                            ->required(),

                        Forms\Components\TextInput::make(
                            'balance'
                        )

                            ->label(
                                __('messages.balance')
                            )

                            ->numeric()

                            ->default(0),

                        Forms\Components\Toggle::make(
                            'is_active'
                        )

                            ->label(
                                __('messages.is_active')
                            )

                            ->default(true),

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
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'cash' =>
                                __('messages.cash'),

                            'bank' =>
                                __('messages.bank'),

                            'mobile' =>
                                __('messages.mobile'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'cash' => 'success',

                        'bank' => 'info',

                        'mobile' => 'warning',

                        default => 'gray',

                    }),

                Tables\Columns\TextColumn::make(
                    'balance'
                )

                    ->label(
                        __('messages.balance')
                    )

                    ->money('MAD')

                    ->sortable(),

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

                    ->dateTime(),

            ])

            ->filters([

                Tables\Filters\SelectFilter::make(
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->options([

                        'cash' =>
                            __('messages.cash'),

                        'bank' =>
                            __('messages.bank'),

                        'mobile' =>
                            __('messages.mobile'),

                    ]),

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
                ListFunds::route('/'),

            'create' =>
                CreateFund::route('/create'),

            'view' =>
                ViewFund::route('/{record}'),

            'edit' =>
                EditFund::route('/{record}/edit'),

        ];
    }
}
