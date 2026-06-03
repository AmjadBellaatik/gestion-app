<?php

namespace App\Filament\Resources\Resellers\Schemas;

use Filament\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ResellerForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | General Information
                |--------------------------------------------------------------------------
                */

                TextInput::make('name')

                    ->label(
                        __('messages.reseller')
                    )

                    ->required()

                    ->maxLength(255),

                TextInput::make('phone')

                    ->label(
                        __('messages.phone')
                    )

                    ->tel()

                    ->maxLength(255),

                TextInput::make('email')

                    ->label(
                        __('messages.email')
                    )

                    ->email()

                    ->maxLength(255),

                TextInput::make('website')

                    ->label(
                        __('messages.website')
                    )

                    ->url()

                    ->placeholder('https://')

                    ->maxLength(255),

                Textarea::make('address')

                    ->label(
                        __('messages.address')
                    )

                    ->columnSpanFull(),

                TextInput::make('credit_balance')

                    ->label(
                        __('messages.credit_balance')
                    )

                    ->numeric()

                    ->default(0),

                TextInput::make('total_orders')

                    ->label(
                        __('messages.total_orders')
                    )

                    ->numeric()

                    ->readOnly()

                    ->dehydrated(false)

                    ->helperText(__('messages.auto_calculated')),

                TextInput::make('total_paid')

                    ->label(
                        __('messages.total_paid')
                    )

                    ->numeric()

                    ->suffix('MAD')

                    ->readOnly()

                    ->dehydrated(false)

                    ->helperText(__('messages.auto_calculated')),

                TextInput::make('current_debt')

                    ->label(
                        __('messages.current_debt')
                    )

                    ->numeric()

                    ->suffix('MAD')

                    ->readOnly()

                    ->dehydrated(false)

                    ->helperText(__('messages.auto_calculated')),

                /*
                |--------------------------------------------------------------------------
                | Company Information
                |--------------------------------------------------------------------------
                */

                Section::make(

                    __('messages.company_information')

                )

                    ->schema([

                        TextInput::make(
                            'ice'
                        )

                            ->label(
                                __('messages.ice')
                            ),

                        TextInput::make(
                            'rc'
                        )

                            ->label(
                                __('messages.rc')
                            ),

                        TextInput::make(
                            'if'
                        )

                            ->label(
                                __('messages.if')
                            ),

                        TextInput::make(
                            'patente'
                        )

                            ->label(
                                __('messages.patente')
                            ),

                        TextInput::make(
                            'representative_name'
                        )

                            ->label(
                                __('messages.representative_name')
                            ),

                    ])

                    ->columns(2),

                /*
                |--------------------------------------------------------------------------
                | Financial Settings
                |--------------------------------------------------------------------------
                */

                Section::make(

                    __('messages.financial_settings')

                )

                    ->schema([

                        TextInput::make(
                            'max_debt'
                        )

                            ->label(
                                __('messages.max_debt')
                            )

                            ->numeric()

                            ->default(200000)

                            ->visible(fn () =>

                                auth()->user()->can(
                                    'manage_reseller_debt'
                                )
                            ),

                        TextInput::make(
                            'credit_days'
                        )

                            ->label(
                                __('messages.credit_days')
                            )

                            ->numeric()

                            ->default(30),

                        Toggle::make(
                            'is_blocked'
                        )

                            ->label(
                                __('messages.is_blocked')
                            )

                            ->visible(fn () =>

                                auth()->user()->can(
                                    'block_reseller'
                                )
                            ),

                        Textarea::make(
                            'blocked_reason'
                        )

                            ->label(
                                __('messages.blocked_reason')
                            )

                            ->columnSpanFull(),

                    ])

                    ->columns(2),

                /*
                |--------------------------------------------------------------------------
                | Company Detail
                |--------------------------------------------------------------------------
                */

                Section::make(

                    __('messages.company_information')

                )

                    ->schema([

                        TextInput::make(
                            'companyDetail.ice'
                        )

                            ->label(
                                __('messages.ice')
                            ),

                        TextInput::make(
                            'companyDetail.rc'
                        )

                            ->label(
                                __('messages.rc')
                            ),

                        TextInput::make(
                            'companyDetail.if'
                        )

                            ->label(
                                __('messages.if')
                            ),

                        TextInput::make(
                            'companyDetail.patente'
                        )

                            ->label(
                                __('messages.patente')
                            ),

                        TextInput::make(
                            'companyDetail.representative_name'
                        )

                            ->label(
                                __('messages.representative_name')
                            ),

                    ])

                    ->visible(fn ($get) =>

                        in_array(

                            $get('type'),

                            [

                                'company',

                                'reseller',

                                'supplier',

                            ]
                        )
                    ),

                /*
                |--------------------------------------------------------------------------
                | ADMINISTRATION DETAILS
                |--------------------------------------------------------------------------
                */

                Section::make(

                    __('messages.administration_details')

                )

                    ->schema([

                        TextInput::make(
                            'administrationDetail.administration_name'
                        )

                            ->label(
                                __('messages.administration_name')
                            ),

                        TextInput::make(
                            'administrationDetail.department'
                        )

                            ->label(
                                __('messages.department')
                            ),

                        TextInput::make(
                            'administrationDetail.responsible_person'
                        )

                            ->label(
                                __('messages.responsible_person')
                            ),

                    ])

                    ->visible(fn ($get) =>

                        $get('type') === 'administration'
                    ),

            ]);
    }
}