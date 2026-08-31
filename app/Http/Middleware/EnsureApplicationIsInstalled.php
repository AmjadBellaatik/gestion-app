<?php

namespace App\Http\Middleware;

use App\Services\Installer\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FIRST-REQUEST ROUTING (main application side).
 *
 * While the application is not installed, every normal request ( / ,
 * /admin , /admin/login , … ) is redirected to /install. Once the lock
 * file exists this middleware is a no-op and never touches the DB.
 *
 * Registered at the head of the `web` group. Installer routes live in
 * their own middleware group, so they are never affected here (no loop).
 */
class EnsureApplicationIsInstalled
{
    /** Paths that must stay reachable while uninstalled. */
    private const ALLOWLIST = [
        'install', 'install/*',
        'up',                       // Laravel health endpoint
        'csp-report',
        'build/*', 'css/*', 'js/*', 'fonts/*', 'images/*', 'assets/*',
        'favicon.ico', 'robots.txt',
    ];

    public function __construct(private readonly InstallationState $state)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        if ($this->state->isInstalled()) {
            return $next($request);
        }

        return redirect()->to('/install');
    }

    private function shouldSkip(Request $request): bool
    {
        if (app()->runningUnitTests() && ! config('installer.enable_in_tests', false)) {
            return true;
        }

        if ($request->is(...self::ALLOWLIST)) {
            return true;
        }

        return false;
    }
}
