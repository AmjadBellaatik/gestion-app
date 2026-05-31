<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\Rules\Password;

class ResetPassword extends BaseResetPassword
{
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('messages.password'))
            ->password()
            ->revealable()
            ->required()
            ->rules([
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ])
            ->helperText(__('messages.password_requirements'))
            ->same('passwordConfirmation')
            ->validationAttribute(__('messages.password'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('messages.password_confirmation'))
            ->password()
            ->revealable()
            ->required()
            ->dehydrated(false)
            ->validationAttribute(__('messages.password_confirmation'));
    }
}
