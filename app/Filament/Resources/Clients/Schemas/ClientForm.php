<?php

namespace App\Filament\Resources\Clients\Schemas;

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

        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [

            Grid::make(2)->schema([

                /*
                |--------------------------------------------------------------
                | LEFT: Identification
                |--------------------------------------------------------------
                */
                Section::make(__('messages.identification'))
                    ->columnSpan(1)
                    ->schema([

                        Select::make('client_type')
                            ->label(__('messages.client_type'))
                            ->options([
                                'person'         => __('messages.person'),
                                'company'        => __('messages.company'),
                                'administration' => __('messages.administration'),
                            ])
                            ->default('person')
                            ->live()
                            ->required(),

                        // Person fields
                        TextInput::make('first_name')
                            ->label(__('messages.first_name'))
                            ->visible(fn ($get) => $get('client_type') === 'person')
                            ->required(fn ($get) => $get('client_type') === 'person'),

                        TextInput::make('last_name')
                            ->label(__('messages.last_name'))
                            ->visible(fn ($get) => $get('client_type') === 'person')
                            ->required(fn ($get) => $get('client_type') === 'person'),

                        TextInput::make('cin')
                            ->label(__('messages.national_id'))
                            ->visible(fn ($get) => $get('client_type') === 'person')
                            ->required(fn ($get) => $get('client_type') === 'person'),

                        DatePicker::make('birth_date')
                            ->label(__('messages.birth_date'))
                            ->visible(fn ($get) => $get('client_type') === 'person'),

                        Select::make('nationality')
                            ->label(__('messages.nationality'))
                            ->options(config('nationalities'))
                            ->searchable()
                            ->visible(fn ($get) => $get('client_type') === 'person'),

                        // Company fields
                        TextInput::make('company_name')
                            ->label(__('messages.company_name'))
                            ->visible(fn ($get) => $get('client_type') === 'company')
                            ->required(fn ($get) => $get('client_type') === 'company'),

                        TextInput::make('ice')
                            ->label(__('messages.ice'))
                            ->visible(fn ($get) => $get('client_type') === 'company')
                            ->required(fn ($get) => $get('client_type') === 'company'),

                        TextInput::make('rc')
                            ->label(__('messages.rc'))
                            ->visible(fn ($get) => $get('client_type') === 'company')
                            ->required(fn ($get) => $get('client_type') === 'company'),

                        TextInput::make('if')
                            ->label(__('messages.if'))
                            ->visible(fn ($get) => $get('client_type') === 'company'),

                        TextInput::make('representative_name')
                            ->label(__('messages.representative_name'))
                            ->visible(fn ($get) => $get('client_type') === 'company'),

                        // Administration fields
                        TextInput::make('administration_name')
                            ->label(__('messages.administration_name'))
                            ->visible(fn ($get) => $get('client_type') === 'administration')
                            ->required(fn ($get) => $get('client_type') === 'administration'),

                        TextInput::make('department')
                            ->label(__('messages.department'))
                            ->visible(fn ($get) => $get('client_type') === 'administration'),

                        TextInput::make('responsible_person')
                            ->label(__('messages.responsible_person'))
                            ->visible(fn ($get) => $get('client_type') === 'administration'),

                    ])
                    ->columns(2),

                /*
                |--------------------------------------------------------------
                | RIGHT: Contact + Status stacked
                |--------------------------------------------------------------
                */
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([

                        Section::make(__('messages.contact_information'))
                            ->schema([

                                Grid::make(2)->schema([
                                    TextInput::make('phone')
                                        ->label(__('messages.phone'))
                                        ->tel(),

                                    TextInput::make('email')
                                        ->label(__('messages.email'))
                                        ->email(),
                                ]),

                                Textarea::make('address')
                                    ->label(__('messages.address'))
                                    ->rows(3)
                                    ->columnSpanFull(),

                            ]),

                        Section::make(__('messages.notes'))
                            ->schema([

                                Textarea::make('notes')
                                    ->label(__('messages.notes'))
                                    ->rows(3)
                                    ->columnSpanFull(),

                            ]),

                        Section::make(__('messages.status'))
                            ->schema([

                                Toggle::make('is_active')
                                    ->label(__('messages.active'))
                                    ->default(true)
                                    ->disabled(fn ($get) => (bool) $get('is_blocked'))
                                    ->dehydrated(),

                                Toggle::make('is_blocked')
                                    ->label(__('messages.blocked'))
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('is_active', false);
                                        } else {
                                            $set('is_active', true);
                                        }
                                    }),

                                Textarea::make('blocked_reason')
                                    ->label(__('messages.blocked_reason'))
                                    ->visible(fn ($get) => (bool) $get('is_blocked'))
                                    ->required(fn ($get) => (bool) $get('is_blocked'))
                                    ->rows(2)
                                    ->columnSpanFull(),

                            ])
                            ->columns(2),

                    ]),

            ]),

        ];
    }
}
