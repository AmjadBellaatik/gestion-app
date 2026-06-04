<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionLifetime
{
    private const MAX_SESSION_HOURS = 8;

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $loginAt = session('login_at');

            if ($loginAt && (now()->timestamp - $loginAt) > self::MAX_SESSION_HOURS * 3600) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('filament.admin.auth.login')
                    ->with('status', __('messages.session_expired'));
            }
        }

        return $next($request);
    }
}
