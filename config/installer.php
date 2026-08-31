<?php

/*
|--------------------------------------------------------------------------
| First-run Installer configuration
|--------------------------------------------------------------------------
|
| Consumed by App\Services\Installer\* and the installer middleware.
| These values intentionally have safe production defaults and rarely
| need to be changed via .env.
|
*/

return [

    /*
    | Absolute path to the installation lock file. Its mere existence means
    | "this application has been installed" — the installer becomes
    | inaccessible and the app boots normally. Never auto-deleted.
    */
    'lock_path' => env('INSTALLER_LOCK_PATH', storage_path('app/installed')),

    /*
    | Directory that holds the transient installation state machine file
    | (storage/app/install/state.json) while the wizard is in progress.
    */
    'state_path' => env('INSTALLER_STATE_PATH', storage_path('app/install')),

    /*
    | Path to the .env file the installer mutates. Null → Laravel's real
    | environment file. Overridden by the installer test-suite so it can
    | run the full flow without touching the developer's .env.
    */
    'env_path' => env('INSTALLER_ENV_PATH'),

    /*
    | STEP 7 runs storage:link + optimize:clear + config/view/event:cache.
    | Disabled by the test-suite; always on in production.
    */
    'run_optimizations' => env('INSTALLER_RUN_OPTIMIZATIONS', true),

    /*
    | When true (production default) the installer treats an application
    | whose `migrations` table already contains rows AND that has a valid
    | APP_KEY as "already installed", and self-heals by writing the lock
    | file. This keeps the wizard away from existing deployments that pull
    | this code without having run `php artisan app:mark-installed`.
    |
    | Disaster recovery note: this checks the `migrations` table, NOT the
    | `users` table — restoring a DB backup keeps its migration history, so
    | an empty `users` table never re-opens the installer.
    */
    'trust_schema' => env('INSTALLER_TRUST_SCHEMA', true),

    /*
    | The installer middleware is inert in the `testing` environment unless
    | this flag is switched on (the installer's own test suite does exactly
    | that). Prevents the redirect-to-/install guard from breaking every
    | other feature test.
    */
    'enable_in_tests' => env('INSTALLER_ENABLE_IN_TESTS', false),

    /*
    | Where to send the user once installation completes.
    */
    'redirect_after' => env('INSTALLER_REDIRECT_AFTER', '/admin/login'),

    /*
    | PHP extensions checked on the requirements screen. "required" blocks
    | the wizard when missing; "optional" is reported but non-blocking.
    | The minimum PHP version is read from composer.json at runtime.
    */
    'requirements' => [
        'php_extensions' => [
            'required' => [
                'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer',
                'xml', 'ctype', 'json', 'fileinfo', 'bcmath', 'curl', 'dom',
            ],
            'optional' => [
                'intl', 'gd', 'zip', 'exif',
            ],
        ],

        // Directories that must be writable by the web server user.
        'writable_paths' => [
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ],
    ],
];
