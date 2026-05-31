<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Schemas\Schema;

class DocumentTypeForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                TextInput::make('name')
                    ->label(
                        __('messages.name')
                    )
                    ->required(),

                TextInput::make('code')
                    ->label(
                        __('messages.code')
                    )
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('prefix')
                    ->label(
                        __('messages.prefix')
                    ),

                Toggle::make('is_active')
                    ->label(
                        __('messages.is_active')
                    )
                    ->default(true),

            ]);
    }
}