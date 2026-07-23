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
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                /*
                |--------------------------------------------------------------
                | Status Bar — full width
                |--------------------------------------------------------------
                */
                Section::make()
                    ->columnSpan(2)
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
                | Contact & General Info — left column
                |--------------------------------------------------------------
                */
                Section::make(__('messages.client_information'))
                    ->columnSpan(1)
                    ->schema([

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

                    ])
                    ->columns(2),

                /*
                |--------------------------------------------------------------
                | Type-specific info — right column (mutually exclusive)
                |--------------------------------------------------------------
                */

                Section::make(__('messages.person_information'))
                    ->columnSpan(1)
                    ->visible(fn (Client $record) => $record->client_type === 'person')
                    ->schema([

                        TextEntry::make('first_name')
                            ->label(__('messages.first_name')),

                        TextEntry::make('last_name')
                            ->label(__('messages.last_name')),

                        TextEntry::make('cin')
                            ->label(__('messages.national_id'))
                            ->placeholder('-'),

                        TextEntry::make('birth_date')
                            ->label(__('messages.birth_date'))
                            ->date()
                            ->placeholder('-'),

                        TextEntry::make('nationality')
                            ->label(__('messages.nationality'))
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state) => \App\Models\Client::nationalityOptions()[$state] ?? $state),

                    ])
                    ->columns(2),

                Section::make(__('messages.company_information'))
                    ->columnSpan(1)
                    ->visible(fn (Client $record) => $record->client_type === 'company')
                    ->schema([

                        TextEntry::make('company_name')
                            ->label(__('messages.company_name'))
                            ->columnSpanFull(),

                        TextEntry::make('ice')
                            ->label(__('messages.ice'))
                            ->placeholder('-'),

                        TextEntry::make('rc')
                            ->label(__('messages.rc'))
                            ->placeholder('-'),

                        TextEntry::make('if')
                            ->label(__('messages.if'))
                            ->placeholder('-'),

                        TextEntry::make('representative_name')
                            ->label(__('messages.representative_name'))
                            ->placeholder('-'),

                    ])
                    ->columns(2),

                Section::make(__('messages.administration_information'))
                    ->columnSpan(1)
                    ->visible(fn (Client $record) => $record->client_type === 'administration')
                    ->schema([

                        TextEntry::make('administration_name')
                            ->label(__('messages.administration_name'))
                            ->columnSpanFull(),

                        TextEntry::make('department')
                            ->label(__('messages.department'))
                            ->placeholder('-'),

                        TextEntry::make('responsible_person')
                            ->label(__('messages.responsible_person'))
                            ->placeholder('-'),

                    ])
                    ->columns(2),

            ]);
    }
}
