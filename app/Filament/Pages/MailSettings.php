<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class MailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 94;

    protected string $view = 'filament.pages.mail-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && (
            Auth::user()?->hasRole('Super Admin') ||
            Auth::user()?->hasRole('Admin')
        );
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.mail_settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    public function mount(): void
    {
        $this->data = $this->loadMailSettings();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make(__('messages.mail_settings'))
                    ->description(__('messages.mail_settings_description'))
                    ->schema([

                        TextInput::make('mail_host')
                            ->label(__('messages.mail_host'))
                            ->placeholder('nodels41-eu.n0c.com')
                            ->maxLength(255),

                        TextInput::make('mail_port')
                            ->label(__('messages.mail_port'))
                            ->numeric()
                            ->default(465)
                            ->placeholder('465'),

                        Select::make('mail_encryption')
                            ->label(__('messages.mail_encryption'))
                            ->options([
                                'ssl' => 'SSL (Port 465)',
                                'tls' => 'TLS (Port 587)',
                                ''    => __('messages.none'),
                            ])
                            ->default('ssl'),

                        TextInput::make('mail_username')
                            ->label(__('messages.mail_username'))
                            ->email()
                            ->placeholder('no-reply@example.com')
                            ->maxLength(255),

                        TextInput::make('mail_password')
                            ->label(__('messages.mail_password'))
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('from_address')
                            ->label(__('messages.mail_from_address'))
                            ->email()
                            ->placeholder('no-reply@example.com')
                            ->helperText(__('messages.mail_from_address_hint'))
                            ->maxLength(255),

                        TextInput::make('from_name')
                            ->label(__('messages.mail_from_name'))
                            ->maxLength(255),

                    ])
                    ->columns(2),

            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = session('company_id');

        Setting::withoutEvents(function () use ($companyId): void {
            foreach ($this->data as $key => $value) {
                Setting::withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'group'      => 'mail',
                        'key'        => $key,
                    ],
                    [
                        'value'     => $value,
                        'type'      => $key === 'mail_port' ? 'integer' : 'string',
                        'is_public' => false,
                    ]
                );
            }
        });

        Notification::make()
            ->title(__('messages.updated_successfully'))
            ->success()
            ->send();
    }

    protected function loadMailSettings(): array
    {
        $companyId = session('company_id');

        return Setting::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('group', 'mail')
            ->pluck('value', 'key')
            ->toArray();
    }
}
