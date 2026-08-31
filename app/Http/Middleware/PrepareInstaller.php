<?php

namespace App\Http\Middleware;

use App\Support\AppKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the installer bootable on a brand-new clone.
 *
 *  - Forces a file-based session + cache for the installer routes so the
 *    wizard works before any database (or the `sessions` / `cache` tables)
 *    exists.
 *  - Generates an APP_KEY the first time the installer is opened, so CSRF,
 *    encrypted cookies and the session all function from step 1. An
 *    existing valid key is left untouched.
 *
 * Must run before StartSession / EncryptCookies — it is the first entry in
 * the `installer` middleware group.
 */
class PrepareInstaller
{
    public function handle(Request $request, Closure $next): Response
    {
        // A bare clone has no `sessions` / `cache` table yet — the installer
        // must not depend on the database. Tests already run the `array`
        // drivers, which are equally DB-free, so leave those alone for
        // predictable session assertions.
        if (! app()->runningUnitTests()) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);
        }

        AppKey::ensure();

        return $next($request);
    }
}
