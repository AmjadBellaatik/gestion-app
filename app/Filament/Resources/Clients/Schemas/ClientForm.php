<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Reseller;

use Filament\Forms;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema

            ->components([

                Grid::make(3)

                    ->schema([

                        Select::make(
                            'client_type'
                        )

                            ->label(
                                __('messages.client_type')
                            )

                            ->options([

                                'person' => __(
                                    'messages.person'
                                ),

                                'company' => __(
                                    'messages.company'
                                ),

                                'administration' => __(
                                    'messages.administration'
                                ),

                            ])

                            ->default('person')

                            ->live()

                            ->required(),

                        Select::make(
                            'reseller_id'
                        )

                            ->label(
                                __('messages.reseller')
                            )

                            ->options(
                                fn () => Reseller::query()
                                    ->where('is_active', true)
                                    ->where('is_blocked', false)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )

                            ->searchable()

                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label(__('messages.name'))->required(),
                                TextInput::make('phone')->label(__('messages.phone'))->tel(),
                                TextInput::make('email')->label(__('messages.email'))->email(),
                                TextInput::make('address')->label(__('messages.address')),
                            ])
                            ->createOptionUsing(fn (array $data) => Reseller::create($data)->id),

                        TextInput::make(
                            'company_name'
                        )

                            ->label(
                                __('messages.company_name')
                            )

                            ->visible(
                                fn ($get) =>

                                    $get(
                                        'client_type'
                                    ) === 'company'
                            )

                            ->required(
                                fn ($get) =>

                                    $get(
                                        'client_type'
                                    ) === 'company'
                            ),

                        TextInput::make(
                            'administration_name'
                        )

                            ->label(
                                __('messages.administration_name')
                            )

                            ->visible(
                                fn ($get) =>

                                    $get(
                                        'client_type'
                                    ) === 'administration'
                            )

                            ->required(
                                fn ($get) =>

                                    $get(
                                        'client_type'
                                    ) === 'administration'
                            ),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | PERSON INFORMATION
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.person_information')
                )

                    ->visible(
                        fn ($get) =>

                            $get(
                                'client_type'
                            ) === 'person'
                    )

                    ->schema([

                        Grid::make(3)

                            ->schema([

                                TextInput::make(
                                    'first_name'
                                )

                                    ->label(
                                        __('messages.first_name')
                                    )

                                    ->required(),

                                TextInput::make(
                                    'last_name'
                                )

                                    ->label(
                                        __('messages.last_name')
                                    )

                                    ->required(),

                                TextInput::make(
                                    'cin'
                                )

                                    ->label(
                                        __('messages.national_id')
                                    )

                                    ->required(),

                                DatePicker::make(
                                    'birth_date'
                                )

                                    ->label(
                                        __('messages.birth_date')
                                    ),

                                Select::make(
                                    'nationality'
                                )

                                    ->label(
                                        __('messages.nationality')
                                    )

                                    ->options(
                                        config(
                                            'nationalities'
                                        )
                                    )

                                    ->searchable(),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | COMPANY INFORMATION
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.company_information')
                )

                    ->visible(
                        fn ($get) =>

                            $get(
                                'client_type'
                            ) === 'company'
                    )

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                TextInput::make(
                                    'ice'
                                )

                                    ->label(
                                        __('messages.ice')
                                    )

                                    ->required(),

                                TextInput::make(
                                    'rc'
                                )

                                    ->label(
                                        __('messages.rc')
                                    )

                                    ->required(),

                                TextInput::make(
                                    'if'
                                )

                                    ->label(
                                        __('messages.if')
                                    )

                                    ->required(),

                                TextInput::make(
                                    'representative_name'
                                )

                                    ->label(
                                        __('messages.representative_name')
                                    )

                                    ->required(),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | ADMINISTRATION INFORMATION
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.administration_information')
                )

                    ->visible(
                        fn ($get) =>

                            $get(
                                'client_type'
                            ) === 'administration'
                    )

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                TextInput::make(
                                    'department'
                                )

                                    ->label(
                                        __('messages.department')
                                    ),

                                TextInput::make(
                                    'responsible_person'
                                )

                                    ->label(
                                        __('messages.responsible_person')
                                    )

                                    ->required(),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | GENERAL INFORMATION
                |--------------------------------------------------------------------------
                */

                Grid::make(2)

                    ->schema([

                        TextInput::make(
                            'phone'
                        )

                            ->label(
                                __('messages.phone')
                            )

                            ->tel(),

                        TextInput::make(
                            'email'
                        )

                            ->label(
                                __('messages.email')
                            )

                            ->email(),

                    ]),

                Textarea::make(
                    'address'
                )

                    ->label(
                        __('messages.address')
                    )

                    ->rows(4)

                    ->columnSpanFull(),

                Textarea::make(
                    'notes'
                )

                    ->label(
                        __('messages.notes')
                    )

                    ->rows(4)

                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                Section::make(__('messages.status'))

                    ->schema([

                        Grid::make(2)->schema([

                            Toggle::make('is_active')
                                ->label(__('messages.active'))
                                ->default(true),

                            Toggle::make('is_blocked')
                                ->label(__('messages.blocked'))
                                ->live()
                                ->afterStateUpdated(
                                    fn ($state, callable $set) =>
                                        $state ? $set('is_active', false) : null
                                ),

                        ]),

                        Textarea::make('blocked_reason')
                            ->label(__('messages.blocked_reason'))
                            ->visible(fn ($get) => (bool) $get('is_blocked'))
                            ->required(fn ($get) => (bool) $get('is_blocked'))
                            ->rows(2)
                            ->columnSpanFull(),

                    ]),

                TextInput::make('balance')
                    ->label(__('messages.balance'))
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->suffix('MAD'),

            ]);
    }
}
