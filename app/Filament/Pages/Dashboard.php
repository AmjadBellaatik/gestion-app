<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.dashboard');
    }

    public function getTitle(): string
    {
        return __('messages.dashboard');
    }

    public function getWidgets(): array
    {
        return [];
    }
}
