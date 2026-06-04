<?php

namespace App\Filament\Resources\Funds\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('cash'),
                TextInput::make('balance')
                    ->numeric()
                    ->default(0.0)
                    ->readOnly()
                    ->dehydrated(false)
                    ->helperText(__('messages.balance_auto_calculated')),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
