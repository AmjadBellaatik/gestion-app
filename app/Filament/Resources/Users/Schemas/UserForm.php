<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('messages.name'))
                    ->required(),

                TextInput::make('email')
                    ->label(__('messages.email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('language')
                    ->label(__('messages.language'))
                    ->options([
                        'fr' => 'Français',
                        'en' => 'English',
                        'ar' => 'العربية',
                    ])
                    ->required()
                    ->default('fr'),

                DateTimePicker::make('email_verified_at')
                    ->label(__('messages.email_verified_at')),

                TextInput::make('password')
                    ->label(__('messages.password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->rules([
                        Password::min(12)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols()
                            ->uncompromised(),
                    ])
                    ->helperText(__('messages.password_requirements')),

                TextInput::make('password_confirmation')
                    ->label(__('messages.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->dehydrated(false)
                    ->same('password')
                    ->requiredWith('password'),
            ]);
    }
}
