<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        /* Prevent indexing / caching of this private ERP */
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');

        /* Clickjacking / framing protection */
        $response->headers->set('X-Frame-Options', 'DENY');

        /* MIME-type sniffing / XSS filters */
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        /* Referrer & permissions */
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');

        /* Cross-origin isolation */
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        /* HSTS — only over HTTPS */
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        /* Content-Security-Policy */
        $this->applyContentSecurityPolicy($request, $response);

        return $response;
    }

    /**
     * Build and attach the CSP header.
     *
     * - Only added to HTML responses (PDF downloads, JSON, binaries are left
     *   untouched so DOMPDF output, exports and API responses are unaffected).
     * - Emitted as Content-Security-Policy-Report-Only until CSP_ENFORCE=true,
     *   so it can never break the UI before violations have been reviewed.
     */
    private function applyContentSecurityPolicy(Request $request, Response $response): void
    {
        $config = config('security.csp');

        if (! ($config['enabled'] ?? false)) {
            return;
        }

        // Restrict to HTML documents only.
        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return;
        }

        $parts = [];

        foreach ($config['directives'] as $directive => $sources) {
            $parts[] = empty($sources)
                ? $directive
                : $directive.' '.implode(' ', $sources);
        }

        // HTTPS-only hardening directives (inert/harmful on local http).
        if ($request->isSecure()) {
            foreach ($config['https_only_directives'] ?? [] as $directive) {
                $parts[] = $directive;
            }
        }

        // Violation reporting (both legacy report-uri and modern report-to target).
        if (! empty($config['report_uri'])) {
            $parts[] = 'report-uri '.$config['report_uri'];
        }

        $policy = implode('; ', $parts);

        $header = ($config['enforce'] ?? false)
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        $response->headers->set($header, $policy);
    }
}
