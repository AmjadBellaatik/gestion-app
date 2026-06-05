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
