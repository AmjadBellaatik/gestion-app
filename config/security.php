<?php

/*
|--------------------------------------------------------------------------
| Content-Security-Policy configuration
|--------------------------------------------------------------------------
|
| Centralised, tunable CSP for the ERP. Emitted by App\Http\Middleware\
| SecurityHeaders. Designed for Laravel 13 + Filament v4 + Livewire v3 +
| Alpine.js, plus the public document-verification page.
|
| WHY 'unsafe-eval' AND 'unsafe-inline' CANNOT BE REMOVED FOR SCRIPTS:
|   - Alpine.js (bundled by Filament) evaluates directive expressions
|     (x-data, x-show, :class, etc.) with the Function() constructor →
|     requires script-src 'unsafe-eval'. Filament does not ship Alpine's
|     CSP build, so this is unavoidable without forking the panel assets.
|   - Filament/Livewire emit inline <script> blocks (Livewire snapshot
|     bootstrap, Alpine inline init) and inline style="" everywhere
|     (x-show toggling, dynamic widths) → require 'unsafe-inline'.
|   - A nonce/hash would force browsers to IGNORE 'unsafe-inline', breaking
|     Alpine's inline directives, while 'unsafe-eval' would still be needed.
|     Net security gain ≈ 0, breakage = high. So we keep 'unsafe-inline'
|     and harden every OTHER directive to least privilege instead.
|
| Toggle CSP_ENFORCE in .env:
|   false (default) → Content-Security-Policy-Report-Only  (observe, never block)
|   true            → Content-Security-Policy               (enforce/block)
*/

return [

    'csp' => [

        // Master switches
        'enabled' => env('CSP_ENABLED', true),
        'enforce' => env('CSP_ENFORCE', false), // start in Report-Only; flip after validation

        // Where the browser POSTs violation reports (logged by CspReportController)
        'report_uri' => env('CSP_REPORT_URI', '/csp-report'),

        // Least-privilege directive map. Each value is a list of allowed sources.
        'directives' => [

            'default-src' => ["'self'"],

            // Scripts: self + Alpine eval + Filament/Livewire inline + chart/tailwind CDNs
            'script-src' => [
                "'self'",
                "'unsafe-eval'",          // Alpine expression evaluation (required)
                "'unsafe-inline'",        // Livewire/Filament/Alpine inline scripts (required)
                'https://cdn.jsdelivr.net',     // Chart.js, Bootstrap bundle
                'https://cdn.tailwindcss.com',  // public document-verification page
            ],

            // Styles: self + pervasive Filament/Alpine inline styles + font/bootstrap CSS
            'style-src' => [
                "'self'",
                "'unsafe-inline'",        // Filament/Alpine inline styles (required)
                'https://fonts.bunny.net',
                'https://cdn.jsdelivr.net',
            ],

            // Images: self, QR/logo data URIs, file-upload blob previews, avatar fallback
            'img-src' => [
                "'self'",
                'data:',
                'blob:',
                'https://ui-avatars.com',
            ],

            // Fonts: Filament local fonts, Bunny font files, data: icon fonts
            'font-src' => [
                "'self'",
                'data:',
                'https://fonts.bunny.net',
            ],

            // XHR/fetch — Livewire talks only to same origin (no websockets configured)
            'connect-src' => ["'self'"],

            // Native PDF preview / same-origin frames only
            'frame-src' => ["'self'"],

            // Web workers (some libs spawn blob workers)
            'worker-src' => ["'self'", 'blob:'],

            'media-src' => ["'self'"],

            // ---- Hardening (Phase 10) ----
            'object-src'      => ["'none'"],   // no Flash/legacy plugins; PDFs are served, not <object>
            'base-uri'        => ["'self'"],   // block <base> hijacking
            'form-action'     => ["'self'"],   // forms post to same origin only
            'frame-ancestors' => ["'self'"],   // anti-clickjacking (X-Frame-Options also DENYs)
        ],

        /*
        | Directives emitted ONLY over HTTPS (inert/again harmful on local http).
        | Mirrors the existing HSTS guard so local http dev is never broken.
        */
        'https_only_directives' => [
            'upgrade-insecure-requests',
            'block-all-mixed-content',
        ],
    ],
];
