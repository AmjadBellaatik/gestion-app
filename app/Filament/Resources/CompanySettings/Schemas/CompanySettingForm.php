<?php

namespace App\Filament\Resources\CompanySettings\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanySettingForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                Section::make(
                    __('messages.company_information')
                )

                    ->schema([

                        Forms\Components\TextInput::make(
                            'name'
                        )

                            ->label(
                                __('messages.name')
                            )

                            ->required()

                            ->maxLength(255),

                        Forms\Components\Select::make(
                            'default_language'
                        )

                            ->label(
                                __('messages.default_language')
                            )

                            ->options([

                                'fr' => 'Francais',

                                'en' => 'English',

                                'ar' => 'Arabic',

                            ])

                            ->default('fr')

                            ->required(),

                    ])

                    ->columns(2),

                \App\Filament\Resources\CompanySettings\Schemas\BrandingSection::make(),

                Section::make(
                    __('messages.legal_information')
                )

                    ->schema([

                        Forms\Components\TextInput::make(
                            'ice'
                        )

                            ->label(
                                __('messages.ice')
                            ),

                        Forms\Components\TextInput::make(
                            'rc'
                        )

                            ->label(
                                __('messages.rc')
                            ),

                        Forms\Components\TextInput::make(
                            'if'
                        )

                            ->label(
                                __('messages.tax_number')
                            ),

                        Forms\Components\TextInput::make(
                            'patente'
                        )

                            ->label(
                                __('messages.patente')
                            ),

                        Forms\Components\TextInput::make(
                            'cnss'
                        )

                            ->label(
                                __('messages.cnss')
                            ),

                    ])

                    ->columns(3),

                Section::make(
                    __('messages.contact_information')
                )

                    ->schema([

                        Forms\Components\TextInput::make(
                            'phone'
                        )

                            ->label(
                                __('messages.phone')
                            ),

                        Forms\Components\TextInput::make(
                            'email'
                        )

                            ->label(
                                __('messages.email')
                            )

                            ->email(),

                        Forms\Components\TextInput::make(
                            'website'
                        )

                            ->label(
                                __('messages.website')
                            ),

                        Forms\Components\TextInput::make(
                            'city'
                        )

                            ->label(
                                __('messages.city')
                            ),

                        Forms\Components\TextInput::make(
                            'country'
                        )

                            ->label(
                                __('messages.country')
                            ),

                        Forms\Components\Textarea::make(
                            'address'
                        )

                            ->label(
                                __('messages.address')
                            )

                            ->columnSpanFull(),

                    ])

                    ->columns(3),

                Section::make(
                    __('messages.footer')
                )

                    ->schema([

                        Forms\Components\Textarea::make(
                            'footer'
                        )

                            ->label(
                                __('messages.footer')
                            )

                            ->rows(4),

                        Forms\Components\Textarea::make(
                            'invoice_footer'
                        )

                            ->label(
                                __('messages.invoice_footer')
                            )

                            ->rows(4),

                    ])

                    ->columns(2),

                Section::make(
                    __('messages.financial_settings')
                )

                    ->schema([

                        Forms\Components\TextInput::make(
                            'currency'
                        )

                            ->label(
                                __('messages.currency')
                            )

                            ->default('MAD')

                            ->maxLength(10),

                        Forms\Components\TextInput::make(
                            'tax_rate'
                        )

                            ->label(
                                __('messages.tax_rate')
                            )

                            ->numeric()

                            ->suffix('%')

                            ->minValue(0)

                            ->maxValue(100),

                    ])

                    ->columns(2),

            ]);
    }
}
