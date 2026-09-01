<?php

namespace App\Http\Middleware;

use App\Services\Installer\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every installer route.
 *
 *  - While the wizard is in progress (transient state, non-final stage) the
 *    request passes through — the controller's own stage gate redirects a
 *    wrong-step request to the correct step. No stray 404s mid-install.
 *  - Once the installation lock exists (or a completed legacy install is
 *    detected) every installer route — GET and POST — returns 404.
 *  - /install/complete is reachable exactly once, immediately after a
 *    successful finalize, via a one-shot session flag; after that it 404s
 *    like the rest.
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

        // The wizard is actively running → never block; the controller
        // handles stage ordering with redirects.
        if ($this->state->isInProgress()) {
            return $next($request);
        }

        // One-shot success page straight after finalize().
        if ($request->routeIs('install.complete') && $request->session()->get('installer.completed')) {
            return $next($request);
        }

        if ($this->state->isInstalled()) {
            abort(404);
        }

        return $next($request);
    }
}
