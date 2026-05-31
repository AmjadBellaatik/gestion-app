<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms;

use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                Forms\Components\TextInput::make(
                    'name'
                )

                    ->required(),

                Forms\Components\TextInput::make(
                    'code'
                )

                    ->label(
                        __('messages.code')
                    )

                    ->disabled()

                    ->dehydrated(false)

                    ->placeholder(
                        'Auto generated'
                    ),

                Forms\Components\Textarea::make(
                    'address'
                ),

                Forms\Components\TextInput::make(
                    'phone'
                ),

                Forms\Components\Toggle::make(
                    'is_active'
                )

                    ->default(true),

                Forms\Components\Select::make(
                    'users'
                )

                    ->relationship(
                        'users',
                        'name'
                    )

                    ->multiple()

                    ->preload()

                    ->searchable(),

            ]);
    }
}
