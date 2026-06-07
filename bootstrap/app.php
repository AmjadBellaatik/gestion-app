<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(

    basePath: dirname(__DIR__)

)

    ->withRouting(

        web: __DIR__.'/../routes/web.php',

        api: __DIR__.'/../routes/api.php',

        apiPrefix: 'api',

        commands: __DIR__.'/../routes/console.php',

        health: '/up',

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
