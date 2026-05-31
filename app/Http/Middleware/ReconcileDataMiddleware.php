<?php

namespace App\Http\Middleware;

use App\Services\Reconciliation\ReconciliationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ReconcileDataMiddleware
{
    // Seconds between reconciliation runs per company.
    // 120 s = run at most once every 2 minutes per active company session.
    private const THROTTLE_TTL = 120;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip Livewire component update round-trips — only run on full page loads.
        if ($request->header('X-Livewire') || ! auth()->check()) {
            return $response;
        }

        $companyId = session('company_id');

        if (! $companyId) {
            return $response;
        }

        $cacheKey = "erp_reconcile_co_{$companyId}";

        if (Cache::has($cacheKey)) {
            return $response;
        }

        // Mark as running before we schedule the work so parallel requests skip it.
        Cache::put($cacheKey, true, self::THROTTLE_TTL);

        // Run after the HTTP response is sent — zero impact on page load time.
        app()->terminating(function () use ($companyId): void {
            try {
                app(ReconciliationService::class)->reconcileCompany((int) $companyId);
            } catch (\Throwable) {
                // Never crash the app due to reconciliation failure.
            }
        });

        return $response;
    }
}
