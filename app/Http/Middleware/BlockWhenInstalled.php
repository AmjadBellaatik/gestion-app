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
 *  - Once installed: the two harmless entrypoints — GET /install and (after
 *    its one-shot view) GET /install/complete — redirect to the login page;
 *    every real step page and EVERY POST endpoint returns 404.
 *  - /install/complete renders exactly once, immediately after a successful
 *    finalize, via a one-shot session flag.
 */
class BlockWhenInstalled
{
    /** GET routes that redirect (rather than 404) once the app is installed. */
    private const REDIRECT_WHEN_INSTALLED = ['install.index', 'install.complete'];

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
            if ($request->isMethod('GET') && $request->routeIs(...self::REDIRECT_WHEN_INSTALLED)) {
                return redirect()->to((string) config('installer.redirect_after', '/admin/login'));
            }

            abort(404);
        }

        return $next($request);
    }
}
