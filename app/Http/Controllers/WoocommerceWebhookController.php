<?php

namespace App\Http\Controllers;

use App\Models\WoocommerceOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WoocommerceWebhookController extends Controller
{
    /**
     * Receive WooCommerce webhook for new / updated orders.
     *
     * WooCommerce signs every request with HMAC-SHA256 using the webhook secret
     * and sends it in the X-WC-Webhook-Signature header (base64-encoded).
     *
     * Set WC_WEBHOOK_SECRET in your .env to enable signature verification.
     * Leave it empty to skip verification (useful while testing locally).
     */
    public function handle(Request $request): Response
    {
        // ── 1. Signature verification ──────────────────────────────────────────
        $secret = config('services.woocommerce.webhook_secret');

        if ($secret) {
            $signature = $request->header('X-WC-Webhook-Signature');

            if (! $signature) {
                Log::warning('WooCommerce webhook: missing signature header');
                return response('Unauthorized', 401);
            }

            $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

            if (! hash_equals($computed, $signature)) {
                Log::warning('WooCommerce webhook: signature mismatch');
                return response('Forbidden', 403);
            }
        }

        // ── 2. Parse payload ───────────────────────────────────────────────────
        $payload = $request->json()->all();

        if (empty($payload['id'])) {
            Log::warning('WooCommerce webhook: payload missing order id', ['payload' => $payload]);
            return response('Bad Request', 400);
        }

        $topic = $request->header('X-WC-Webhook-Topic', '');

        // ── 3. Upsert order ────────────────────────────────────────────────────
        try {
            WoocommerceOrder::updateOrCreate(
                ['wc_order_id' => $payload['id']],
                WoocommerceOrder::fromPayload($payload)
            );

            Log::info("WooCommerce webhook [{$topic}]: order #{$payload['id']} stored.");
        } catch (\Throwable $e) {
            Log::error('WooCommerce webhook: failed to store order', [
                'order_id' => $payload['id'],
                'error'    => $e->getMessage(),
            ]);
            return response('Internal Server Error', 500);
        }

        return response('OK', 200);
    }
}
