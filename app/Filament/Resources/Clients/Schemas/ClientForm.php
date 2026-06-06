<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Reseller;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Client create/edit form — desktop-first, full-width, responsive.
 *
 * Layout strategy:
 *   - Every section spans the full page width (no wasted right gutter).
 *   - Responsive column counts: 1 (mobile) → 2 (tablet/md) → 3 (desktop/xl).
 *   - Client type is a segmented control; only the relevant type's fields render.
 */
class ClientForm
{
    /** Responsive grid used by every section: mobile 1 / tablet 2 / desktop 3. */
    private const RESPONSIVE = ['default' => 1, 'md' => 2, 'xl' => 3];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1) // sections stack full-width
            ->components(static::components());
    }

    public static function components(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | 1. CLIENT TYPE — segmented selector, full width
            |------------------------------------------------------------------
            */
            Section::make(__('messages.client_type'))
                ->schema([
                    ToggleButtons::make('client_type')
                        ->hiddenLabel()
                        ->options([
                            'person'         => __('messages.person'),
                            'company'        => __('messages.company'),
                            'administration' => __('messages.administration'),
                        ])
                        ->icons([
                            'person'         => 'heroicon-o-user',
                            'company'        => 'heroicon-o-building-office-2',
                            'administration' => 'heroicon-o-building-library',
                        ])
                        ->default('person')
                        ->inline()
                        ->live()
                        ->required(),
                ]),

            /*
            |------------------------------------------------------------------
            | 2. MAIN INFORMATION — type-specific fields, 3-col responsive
            |------------------------------------------------------------------
            */
            Section::make(__('messages.main_information'))
                ->columns(self::RESPONSIVE)
                ->schema([

                    // ---- Person ----
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
                        ->native(false)
                        ->visible(fn ($get) => $get('client_type') === 'person'),

                    Select::make('nationality')
                        ->label(__('messages.nationality'))
                        ->options(config('nationalities'))
                        ->searchable()
                        ->visible(fn ($get) => $get('client_type') === 'person'),

                    // ---- Company ----
                    TextInput::make('company_name')
                        ->label(__('messages.company_name'))
                        ->visible(fn ($get) => $get('client_type') === 'company')
                        ->required(fn ($get) => $get('client_type') === 'company')
                        ->columnSpan(['default' => 1, 'md' => 2, 'xl' => 1]),

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

                    // ---- Administration ----
                    TextInput::make('administration_name')
                        ->label(__('messages.administration_name'))
                        ->visible(fn ($get) => $get('client_type') === 'administration')
                        ->required(fn ($get) => $get('client_type') === 'administration')
                        ->columnSpan(['default' => 1, 'md' => 2, 'xl' => 1]),

                    TextInput::make('department')
                        ->label(__('messages.department'))
                        ->visible(fn ($get) => $get('client_type') === 'administration'),

                    TextInput::make('responsible_person')
                        ->label(__('messages.responsible_person'))
                        ->visible(fn ($get) => $get('client_type') === 'administration'),
                ]),

            /*
            |------------------------------------------------------------------
            | 3. CONTACT INFORMATION — 3-col responsive
            |------------------------------------------------------------------
            */
            Section::make(__('messages.contact_information'))
                ->columns(self::RESPONSIVE)
                ->schema([
                    TextInput::make('phone')
                        ->label(__('messages.phone'))
                        ->tel(),

                    TextInput::make('email')
                        ->label(__('messages.email'))
                        ->email(),

                    Select::make('reseller_id')
                        ->label(__('messages.reseller'))
                        ->relationship('reseller', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('-'),

                    Textarea::make('address')
                        ->label(__('messages.address'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            /*
            |------------------------------------------------------------------
            | 4. ADDITIONAL INFORMATION — notes + status, 3-col responsive
            |------------------------------------------------------------------
            */
            Section::make(__('messages.additional_information'))
                ->columns(self::RESPONSIVE)
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
                            $set('is_active', ! $state);
                        }),

                    Textarea::make('blocked_reason')
                        ->label(__('messages.blocked_reason'))
                        ->visible(fn ($get) => (bool) $get('is_blocked'))
                        ->required(fn ($get) => (bool) $get('is_blocked'))
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label(__('messages.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
