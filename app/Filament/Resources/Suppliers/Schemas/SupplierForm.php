<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                TextInput::make('name')
                    ->label(
                        __('messages.supplier')
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

                Textarea::make('address')
                    ->label(
                        __('messages.address')
                    )
                    ->columnSpanFull(),

                TextInput::make('balance')
                    ->label(
                        __('messages.balance')
                    )
                    ->numeric()
                    ->default(0),

                TextInput::make('total_purchases')
                    ->label(
                        __('messages.total_purchases')
                    )
                    ->numeric()
                    ->default(0),

            ]);
    }
}