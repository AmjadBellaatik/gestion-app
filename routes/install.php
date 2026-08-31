<?php

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| First-run installer routes
|--------------------------------------------------------------------------
|
| Loaded from bootstrap/app.php with the dedicated `installer` middleware
| group (file session + CSRF + APP_KEY bootstrap, NO database middleware).
| Every route is additionally guarded by BlockWhenInstalled, so the whole
| wizard 404s the moment the lock file exists.
|
*/

Route::middleware('installer')
    ->prefix('install')
    ->name('install.')
    ->group(function () {

        Route::get('/', [InstallController::class, 'index'])->name('index');
        Route::get('/complete', [InstallController::class, 'complete'])->name('complete');

        Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
        Route::post('/requirements', [InstallController::class, 'storeRequirements'])->name('requirements.store');

        Route::get('/database', [InstallController::class, 'database'])->name('database');
        Route::post('/database/test', [InstallController::class, 'testDatabase'])
            ->middleware('throttle:20,1')
            ->name('database.test');
        Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');

        Route::get('/application', [InstallController::class, 'application'])->name('application');
        Route::post('/application', [InstallController::class, 'storeApplication'])->name('application.store');

        Route::get('/initialize', [InstallController::class, 'initialize'])->name('initialize');
        Route::post('/initialize', [InstallController::class, 'runInitialize'])->name('initialize.run');

        Route::get('/company', [InstallController::class, 'company'])->name('company');
        Route::post('/company', [InstallController::class, 'storeCompany'])->name('company.store');

        Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
        Route::post('/admin', [InstallController::class, 'storeAdmin'])->name('admin.store');

        Route::get('/finalize', [InstallController::class, 'finalize'])->name('finalize');
        Route::post('/finalize', [InstallController::class, 'runFinalize'])->name('finalize.run');
    });
