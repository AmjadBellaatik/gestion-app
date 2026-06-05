<?php

namespace App\Filament\Pages;

use BackedEnum;

use Filament\Schemas\Schema;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

use Filament\Notifications\Notification;

use Filament\Pages\Page;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-user';

    protected static bool $shouldRegisterNavigation =
        true;

    protected static ?int $navigationSort =
        92;

    protected string $view =
        'filament.pages.profile';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.profile_settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([

            'name' => $user->name,

            'email' => $user->email,

            'phone' => $user->phone,

            'address' => $user->address,

            'language' => $user->language,

            'profile_picture' => $user->profile_picture,

        ]);
    }

    public function form(
        Schema $schema
    ): Schema {

        return $schema

            ->components([

                FileUpload::make(
                    'profile_picture'
                )

                    ->label(
                        __('messages.profile_picture')
                    )

                    ->avatar()

                    ->image()

                    ->imageEditor()

                    ->imageCropAspectRatio('1:1')

                    ->directory(
                        'profile_pictures'
                    )

                    ->disk('public'),

                TextInput::make(
                    'name'
                )

                    ->required(),

                TextInput::make(
                    'email'
                )

                    ->email()

                    ->required(),

                TextInput::make(
                    'phone'
                ),

                Textarea::make(
                    'address'
                ),

                Select::make(
                    'language'
                )

                    ->options([

                        'fr' => 'Français',

                        'en' => 'English',

                        'ar' => 'العربية',

                    ]),

                TextInput::make('password')
                    ->label(__('messages.new_password'))
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->rules([
                        Password::min(12)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols()
                            ->uncompromised(),
                    ])
                    ->helperText(__('messages.password_requirements'))
                    ->nullable(),

                TextInput::make('password_confirmation')
                    ->label(__('messages.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->dehydrated(false)
                    ->same('password')
                    ->requiredWith('password')
                    ->nullable(),

            ])

            ->statePath('data');
    }

    public function save(): void
    {
        $user = Auth::user();

        $user->update(
            $this->form->getState()
        );

        Notification::make()

            ->title(
                __('messages.profile_updated')
            )

            ->success()

            ->send();
    }
}
