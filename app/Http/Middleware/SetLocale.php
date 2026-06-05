<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        // PRIORITY:
        // 1. SESSION
        // 2. USER LANGUAGE
        // 3. DEFAULT FR

        $locale = session('locale');

        if (! $locale && auth()->check()) {

            $locale =
                auth()->user()->language;

        }

        if (! $locale) {

            $locale = 'fr';

        }

        app()->setLocale($locale);

        if ($locale === 'ar') {
            \Illuminate\Support\Number::useLocale('ar@numbers=latn');
        }

        return $next($request);
    }
}