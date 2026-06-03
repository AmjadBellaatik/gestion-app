<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ReconcileDataMiddleware;
use App\Http\Middleware\SetCompany;
use App\Http\Middleware\SetLocale;
use App\Models\Company;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;

use Filament\Actions\Action;
use Filament\Navigation\NavigationGroup;

use Filament\Panel;
use Filament\PanelProvider;

use Filament\View\PanelsRenderHook;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

use Illuminate\Routing\Middleware\SubstituteBindings;

use Illuminate\Session\Middleware\StartSession;

use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(
        Panel $panel
    ): Panel {

        return $panel

            ->default()

            ->id('admin')

            ->path('admin')

            ->login()

            ->passwordReset(
                requestAction: \App\Filament\Auth\RequestPasswordReset::class,
                resetAction: \App\Filament\Auth\ResetPassword::class,
            )

            ->databaseNotifications()

            ->databaseNotificationsPolling('30s')

            ->sidebarFullyCollapsibleOnDesktop()

            ->brandName('')

            ->favicon(
                fn (): ?string => $this->getActiveCompanyLogoUrl()
            )

            ->colors(fn (): array => [
                'primary' => $this->getActiveCompany()?->primary_color ?: '#f59e0b',
            ])

            /*
            |--------------------------------------------------------------------------
            | Navigation Groups
            |--------------------------------------------------------------------------
            */

            ->navigationGroups([

                NavigationGroup::make()
                    ->label(fn () => __('messages.commercial'))
                    ->collapsed(),

                NavigationGroup::make()
                    ->label(fn () => __('messages.motorcycles'))
                    ->collapsed(),

                NavigationGroup::make()
                    ->label(fn () => __('messages.stock_management'))
                    ->collapsed(),

                NavigationGroup::make()
                    ->label(fn () => __('messages.workshop'))
                    ->collapsed(),

                NavigationGroup::make()
                    ->label(fn () => __('messages.accounting'))
                    ->collapsed(),

                NavigationGroup::make()
                    ->label(fn () => __('messages.reports'))
                    ->collapsed(),

                NavigationGroup::make()
                    ->label(fn () => __('messages.settings'))
                    ->collapsed(),

            ])

            /*
            |--------------------------------------------------------------------------
            | Resources
            |--------------------------------------------------------------------------
            */

            ->discoverResources(

                in: app_path(
                    'Filament/Resources'
                ),

                for: 'App\\Filament\\Resources'

            )

            /*
            |--------------------------------------------------------------------------
            | Pages
            |--------------------------------------------------------------------------
            */

            ->pages([

                \App\Filament\Pages\Dashboard::class,

            ])

            ->discoverPages(

                in: app_path(
                    'Filament/Pages'
                ),

                for: 'App\\Filament\\Pages'

            )

            /*
            |--------------------------------------------------------------------------
            | Widgets
            |--------------------------------------------------------------------------
            */

            ->widgets([])

            /*
            |--------------------------------------------------------------------------
            | User Menu
            |--------------------------------------------------------------------------
            */

            ->userMenuItems([

                Action::make('profile')

                    ->label(
                        __('messages.profile')
                    )

                    ->url(
                        fn (): string => \App\Filament\Pages\Profile::getUrl()
                    )

                    ->icon(
                        'heroicon-o-user'
                    ),

            ])

            /*
            |--------------------------------------------------------------------------
            | Company Switcher
            |--------------------------------------------------------------------------
            */

            ->renderHook(

                PanelsRenderHook::TOPBAR_START,

                fn (): string => view(
                    'filament.company-switcher'
                )->render()

            )

            ->renderHook(

                PanelsRenderHook::HEAD_END,

                fn (): string => view(
                    'filament.company-visual-identity',
                    [
                        'company' => $this->getActiveCompany(),
                    ]
                )->render()

            )

            /*
            |--------------------------------------------------------------------------
            | Language Switcher
            |--------------------------------------------------------------------------
            */

            ->renderHook(

                PanelsRenderHook::TOPBAR_END,

                fn (): string => view(
                    'filament.components.language-switcher'
                )->render()

            )

            ->renderHook(

                PanelsRenderHook::FOOTER,

                fn (): string => view(
                    'filament.components.developer-footer'
                )->render()

            )

            /*
            |--------------------------------------------------------------------------
            | Middleware
            |--------------------------------------------------------------------------
            */

            ->middleware([

                EncryptCookies::class,

                AddQueuedCookiesToResponse::class,

                StartSession::class,

                AuthenticateSession::class,

                ShareErrorsFromSession::class,

                PreventRequestForgery::class,

                SubstituteBindings::class,

                DisableBladeIconComponents::class,

                DispatchServingFilamentEvent::class,

                SetLocale::class,

                SetCompany::class,

                ReconcileDataMiddleware::class,

            ])

            ->authMiddleware([

                Authenticate::class,

            ]);
    }

    protected function getActiveCompany(): ?Company
    {
        if (! auth()->check()) {

            return null;

        }

        $companies = auth()
            ->user()
            ->companies()
            ->where(
                'companies.name',
                '!=',
                'Default Company'
            );

        if (session()->has('company_id')) {

            $activeCompany = (clone $companies)
                ->where(
                    'companies.id',
                    session('company_id')
                )
                ->first();

            if ($activeCompany) {

                return $activeCompany;

            }

        }

        return $companies->first();
    }

    protected function getActiveCompanyLogoUrl(): ?string
    {
        $logo = $this->getActiveCompany()?->logo;

        if (! $logo) {

            return null;

        }

        return asset('storage/' . ltrim($logo, '/'));
    }
}
