<?php

use Illuminate\Foundation\Inspiring;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Inspire Command
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {

    $this->comment(

        Inspiring::quote()

    );

})->purpose(

    'Display an inspiring quote'

);

/*
|--------------------------------------------------------------------------
| ERP Backup Scheduler
|--------------------------------------------------------------------------
*/

Schedule::command(

    'backup:run'

)->dailyAt('02:00');

Schedule::command(

    'backup:clean'

)->dailyAt('03:00');

/*
|--------------------------------------------------------------------------
| Laravel Optimizations
|--------------------------------------------------------------------------
*/

Schedule::command(

    'model:prune'

)->daily();

/*
|--------------------------------------------------------------------------
| ERP Data Reconciliation
|--------------------------------------------------------------------------
|
| Runs every 5 minutes as a safety net: creates any missing warranties,
| stock movements, payment transactions, and fixes stale sale totals.
| The middleware already runs per page-load (throttled to once / 2 min),
| so this schedule catches anything missed during server downtime.
|
*/

Schedule::command('erp:reconcile')->everyFiveMinutes()->withoutOverlapping();