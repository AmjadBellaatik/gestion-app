<?php

namespace App\Filament\Resources\Resellers\Schemas;

use Filament\Infolists\Components\TextEntry;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ResellerInfolist
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | GENERAL INFORMATION
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.general_information')
                )

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                TextEntry::make(
                                    'name'
                                )

                                    ->label(
                                        __('messages.reseller')
                                    ),

                                TextEntry::make(
                                    'phone'
                                )

                                    ->label(
                                        __('messages.phone')
                                    )

                                    ->placeholder('-'),

                                TextEntry::make(
                                    'email'
                                )

                                    ->label(
                                        __('messages.email')
                                    )

                                    ->placeholder('-'),

                                TextEntry::make(
                                    'address'
                                )

                                    ->label(
                                        __('messages.address')
                                    )

                                    ->placeholder('-'),

                                TextEntry::make(
                                    'credit_balance'
                                )

                                    ->label(
                                        __('messages.credit_balance')
                                    )

                                    ->money('MAD'),

                                TextEntry::make(
                                    'total_orders'
                                )

                                    ->label(
                                        __('messages.total_orders')
                                    ),

                                TextEntry::make(
                                    'total_paid'
                                )

                                    ->label(
                                        __('messages.total_paid')
                                    )

                                    ->money('MAD'),

                                TextEntry::make(
                                    'created_at'
                                )

                                    ->label(
                                        __('messages.created_at')
                                    )

                                    ->dateTime(),

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

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                TextEntry::make(
                                    'ice'
                                )

                                    ->label(
                                        __('messages.ice')
                                    )

                                    ->placeholder('-'),

                                TextEntry::make(
                                    'rc'
                                )

                                    ->label(
                                        __('messages.rc')
                                    )

                                    ->placeholder('-'),

                                TextEntry::make(
                                    'if'
                                )

                                    ->label(
                                        __('messages.if')
                                    )

                                    ->placeholder('-'),

                                TextEntry::make(
                                    'patente'
                                )

                                    ->label(
                                        __('messages.patente')
                                    )

                                    ->placeholder('-'),

                                TextEntry::make(
                                    'representative_name'
                                )

                                    ->label(
                                        __('messages.representative_name')
                                    )

                                    ->placeholder('-'),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | FINANCIAL SETTINGS
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.financial_settings')
                )

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                TextEntry::make(
                                    'max_debt'
                                )

                                    ->label(
                                        __('messages.max_debt')
                                    )

                                    ->money('MAD'),

                                TextEntry::make(
                                    'credit_days'
                                )

                                    ->label(
                                        __('messages.credit_days')
                                    ),

                                TextEntry::make(
                                    'is_blocked'
                                )

                                    ->label(
                                        __('messages.is_blocked')
                                    )

                                    ->badge()

                                    ->formatStateUsing(
                                        fn ($state) =>

                                            $state

                                                ? __('messages.yes')

                                                : __('messages.no')
                                    ),

                                TextEntry::make(
                                    'blocked_reason'
                                )

                                    ->label(
                                        __('messages.blocked_reason')
                                    )

                                    ->placeholder('-'),

                            ]),

                    ]),

            ]);
    }
}