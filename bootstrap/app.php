<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(

    basePath: dirname(__DIR__)

)

    ->withRouting(

        web: __DIR__.'/../routes/web.php',

        api: __DIR__.'/../routes/api.php',

        apiPrefix: 'api',

        commands: __DIR__.'/../routes/console.php',

        health: '/up',

        then: function () {

            // First-run installer — self-contained middleware group, no DB.
            Route::group([], __DIR__.'/../routes/install.php');

        },

    )

    ->withMiddleware(function (
        Middleware $middleware
    ) {

        /*
        |--------------------------------------------------------------------------
        | PHASE 0 — Consolidate guest redirects on Filament login
        |--------------------------------------------------------------------------
        | All `auth`-middleware guest redirects (and session-expiry redirects)
        | now point at the Filament login page instead of the legacy Breeze
        | route('login'). This decouples the framework from routes/auth.php so the
        | legacy auth stack can be retired safely.
        */
        $middleware->redirectGuestsTo(
            fn () => route('filament.admin.auth.login')
        );

        $middleware->append([

            \App\Http\Middleware\SecurityHeaders::class,

        ]);

        /*
        |--------------------------------------------------------------------------
        | FIRST-RUN INSTALLER
        |--------------------------------------------------------------------------
        | While the app is not installed, every request (web, Filament panel,
        | anything) is redirected to /install. Registered as the very first
        | global middleware so the redirect fires before any DB-touching
        | middleware, and regardless of a route's own middleware stack.
        | Completely inert once the installation lock file exists.
        */
        $middleware->prepend(\App\Http\Middleware\EnsureApplicationIsInstalled::class);

        /*
        | Dedicated installer stack: file-based session + CSRF + APP_KEY
        | bootstrap, and NOTHING that reads the database (so it boots on a
        | bare clone). Guarded by BlockWhenInstalled.
        */
        $middleware->group('installer', [
            \App\Http\Middleware\PrepareInstaller::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\BlockWhenInstalled::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | GLOBAL MIDDLEWARE
        |--------------------------------------------------------------------------
        */

        $middleware->web(append: [

            \App\Http\Middleware\SetLocale::class,

            \App\Http\Middleware\SetCompany::class,

            \App\Http\Middleware\SessionLifetime::class,

            \App\Http\Middleware\ConvertArabicNumerals::class,

        ]);

        /*
        |--------------------------------------------------------------------------
        | MIDDLEWARE ALIASES
        |--------------------------------------------------------------------------
        */

        $middleware->alias([

            'role' =>
                \Spatie\Permission\Middleware\RoleMiddleware::class,

            'permission' =>
                \Spatie\Permission\Middleware\PermissionMiddleware::class,

        ]);

    })

    ->withExceptions(function (
        Exceptions $exceptions
    ) {

        //

    })

    ->create();
