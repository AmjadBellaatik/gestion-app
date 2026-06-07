<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Collects browser CSP violation reports (Phase 8 — Report-Only observation).
 *
 * Browsers POST violations here as application/csp-report (legacy report-uri)
 * or application/reports+json (Reporting API). We log a compact summary to the
 * dedicated "csp" channel so blocked resources can be reviewed before enforcing.
 *
 * Public + CSRF-exempt by necessity: the browser sends these without a session
 * or token. Hardened by tight throttling on the route.
 */
class CspReportController extends Controller
{
    public function store(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true) ?: [];

        // Normalise both legacy ({"csp-report":{...}}) and Reporting API ([{...}]) shapes.
        $reports = isset($payload['csp-report']) ? [$payload['csp-report']] : (array) $payload;

        foreach ($reports as $report) {
            $body = $report['body'] ?? $report; // Reporting API nests under "body"

            Log::channel('csp')->warning('CSP violation', array_filter([
                'blocked_uri'         => $body['blocked-uri']        ?? $body['blockedURL']       ?? null,
                'violated_directive'  => $body['violated-directive'] ?? $body['effectiveDirective'] ?? null,
                'document_uri'        => $body['document-uri']       ?? $body['documentURL']       ?? null,
                'source_file'         => $body['source-file']        ?? $body['sourceFile']        ?? null,
                'line'                => $body['line-number']        ?? $body['lineNumber']        ?? null,
                'disposition'         => $body['disposition']        ?? null,
            ], fn ($v) => $v !== null));
        }

        // 204: accepted, nothing to return.
        return response()->noContent();
    }
}
