<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

use App\Models\LoginLog;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Event::listen(Login::class, function (Login $event): void {
            LoginLog::create([
                'user_id'      => $event->user->id,
                'email'        => $event->user->email,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'successful'   => true,
                'logged_in_at' => now(),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            LoginLog::create([
                'email'      => request('email'),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'successful' => false,
            ]);
        });
    }
}
