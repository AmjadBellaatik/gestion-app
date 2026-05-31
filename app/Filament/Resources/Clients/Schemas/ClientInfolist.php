<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Client;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema

            ->components([

                /*
                |--------------------------------------------------------------
                | Status Bar
                |--------------------------------------------------------------
                */

                Section::make()
                    ->schema([
                        Grid::make(4)->schema([

                            TextEntry::make('client_type')
                                ->label(__('messages.client_type'))
                                ->badge()
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'person'         => __('messages.person'),
                                    'company'        => __('messages.company'),
                                    'administration' => __('messages.administration'),
                                    default          => $state,
                                }),

                            IconEntry::make('is_active')
                                ->label(__('messages.active'))
                                ->boolean(),

                            TextEntry::make('is_blocked')
                                ->label(__('messages.status'))
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state
                                    ? __('messages.blocked')
                                    : __('messages.active'))
                                ->color(fn ($state) => $state ? 'danger' : 'success'),

                            TextEntry::make('outstanding_balance')
                                ->label(__('messages.outstanding_balance'))
                                ->money('MAD')
                                ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                        ]),

                        TextEntry::make('blocked_reason')
                            ->label(__('messages.blocked_reason'))
                            ->placeholder('-')
                            ->visible(fn (Client $record) => $record->is_blocked)
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),

                /*
                |--------------------------------------------------------------
                | Contact & General Info
                |--------------------------------------------------------------
                */

                Section::make(__('messages.client_information'))
                    ->schema([

                        Grid::make(2)->schema([

                            TextEntry::make('display_name')
                                ->label(__('messages.client')),

                            TextEntry::make('reseller.name')
                                ->label(__('messages.reseller'))
                                ->placeholder('-'),

                            TextEntry::make('phone')
                                ->label(__('messages.phone'))
                                ->placeholder('-'),

                            TextEntry::make('email')
                                ->label(__('messages.email'))
                                ->placeholder('-'),

                            TextEntry::make('address')
                                ->label(__('messages.address'))
                                ->placeholder('-')
                                ->columnSpanFull(),

                            TextEntry::make('notes')
                                ->label(__('messages.notes'))
                                ->placeholder('-')
                                ->columnSpanFull(),

                        ]),

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
                        fn ($record) =>

                            $record->client_type === 'person'
                    )

                    ->schema([

                        Grid::make(3)

                            ->schema([

                                TextEntry::make(
                                    'first_name'
                                )

                                    ->label(
                                        __('messages.first_name')
                                    ),

                                TextEntry::make(
                                    'last_name'
                                )

                                    ->label(
                                        __('messages.last_name')
                                    ),

                                TextEntry::make(
                                    'cin'
                                )

                                    ->label(
                                        __('messages.national_id')
                                    ),

                                TextEntry::make(
                                    'birth_date'
                                )

                                    ->label(
                                        __('messages.birth_date')
                                    )

                                    ->date(),

                                TextEntry::make(
                                    'nationality'
                                )

                                    ->label(
                                        __('messages.nationality')
                                    )

                                    ->formatStateUsing(
                                        fn ($state) =>

                                            config(
                                                'nationalities'
                                            )[$state]

                                            ?? $state
                                    ),

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
                        fn ($record) =>

                            $record->client_type === 'company'
                    )

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                TextEntry::make(
                                    'company_name'
                                )

                                    ->label(
                                        __('messages.company_name')
                                    ),

                                TextEntry::make(
                                    'ice'
                                )

                                    ->label(
                                        __('messages.ice')
                                    ),

                                TextEntry::make(
                                    'rc'
                                )

                                    ->label(
                                        __('messages.rc')
                                    ),

                                TextEntry::make(
                                    'if'
                                )

                                    ->label(
                                        __('messages.if')
                                    ),

                                TextEntry::make(
                                    'representative_name'
                                )

                                    ->label(
                                        __('messages.representative_name')
                                    ),

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
                        fn ($record) =>

                            $record->client_type === 'administration'
                    )

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                TextEntry::make(
                                    'administration_name'
                                )

                                    ->label(
                                        __('messages.administration_name')
                                    ),

                                TextEntry::make(
                                    'department'
                                )

                                    ->label(
                                        __('messages.department')
                                    ),

                                TextEntry::make(
                                    'responsible_person'
                                )

                                    ->label(
                                        __('messages.responsible_person')
                                    ),

                            ]),

                    ]),

            ]);
    }
}