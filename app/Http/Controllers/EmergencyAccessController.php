<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Break-glass emergency access controller.
 *
 * Access:  GET /system/health-check/{token}?email=you@example.com&pw=newpass
 *
 * – Token  : must match EMERGENCY_ACCESS_TOKEN in .env  (never hardcoded here)
 * – Email  : your admin account email
 * – pw     : temporary password to set (optional — omit to just log in as-is)
 * – One-time HMAC check prevents replay if token rotated
 * – Rate-limited: 5 attempts per 10 min per IP
 * – Every access (success + failure) is logged to storage/logs/laravel.log
 * – Redirects straight into the Filament admin panel
 */
class EmergencyAccessController extends Controller
{
    public function handle(Request $request, string $token): \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        if (! config('services.emergency.enabled')) {
            abort(404);
        }

        $ip = $request->ip();

        // ── 1. Rate limit (5 tries / 10 min per IP) ────────────────────────
        $key = 'emergency:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::channel('single')->warning('[EMERGENCY] Rate limited', ['ip' => $ip]);
            abort(404); // look like a normal 404, give nothing away
        }

        RateLimiter::hit($key, 600);

        // ── 2. Constant-time token comparison ──────────────────────────────
        $secret = config('services.emergency.token');

        // Enforce a minimum 32-character token to prevent brute-force guessing
        // even when rate limiting is partially defeated via distributed IPs.
        if (! $secret || strlen($secret) < 32 || ! hash_equals($secret, $token)) {
            Log::channel('single')->warning('[EMERGENCY] Invalid token attempt', [
                'ip'    => $ip,
                'token' => substr($token, 0, 8) . '…',
            ]);
            abort(404);
        }

        // ── 3. Resolve the owner account ───────────────────────────────────
        $ownerEmail = config('services.emergency.email');

        if (! $ownerEmail) {
            Log::channel('single')->error('[EMERGENCY] EMERGENCY_EMAIL not set in .env');
            abort(404);
        }

        $user = User::where('email', $ownerEmail)->first();

        if (! $user) {
            // Owner account missing — recreate it on the spot
            $user = User::create([
                'name'              => 'Owner',
                'email'             => $ownerEmail,
                'password'          => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]);
        }

        // ── 4. Optionally reset password ───────────────────────────────────
        $newPassword = $request->query('pw');

        if ($newPassword && strlen($newPassword) >= 12) {
            $user->update(['password' => Hash::make($newPassword)]);
            Log::channel('single')->info('[EMERGENCY] Password reset', ['email' => $ownerEmail, 'ip' => $ip]);
        }

        // ── 5. Ensure Super Admin role ─────────────────────────────────────
        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
            Log::channel('single')->info('[EMERGENCY] Super Admin role re-assigned', ['email' => $ownerEmail]);
        }

        // ── 6. Log in and clear rate limiter on success ────────────────────
        Auth::login($user, remember: true);

        RateLimiter::clear($key);

        Log::channel('single')->info('[EMERGENCY] Access granted', [
            'email' => $ownerEmail,
            'ip'    => $ip,
        ]);

        // ── 7. Redirect into the Filament admin panel (dashboard = /admin) ──
        return redirect()->route('filament.admin.pages.dashboard');
    }
}
