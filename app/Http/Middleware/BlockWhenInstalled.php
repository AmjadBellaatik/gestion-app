<?php

namespace App\Http\Middleware;

use App\Services\Installer\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every installer route. Once the application is installed the whole
 * wizard — welcome, database, application, company, admin, finalize
 * endpoints — returns 404 so it cannot be re-run to replace the
 * administrator.
 *
 * In the `testing` environment the installer is invisible unless
 * installer.enable_in_tests is switched on (its own test suite does this).
 */
class BlockWhenInstalled
{
    public function __construct(private readonly InstallationState $state)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests() && ! config('installer.enable_in_tests', false)) {
            abort(404);
        }

        // The post-install success page stays reachable — it performs no
        // action and only links to the login screen.
        if ($request->routeIs('install.complete')) {
            return $next($request);
        }

        if ($this->state->isInstalled()) {
            abort(404);
        }

        return $next($request);
    }
}
