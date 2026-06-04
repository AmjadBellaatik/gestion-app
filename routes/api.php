<?php

use App\Http\Controllers\WoocommerceWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| No CSRF — webhook calls come from external services (WooCommerce).
| Signature verification is handled inside each controller.
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:30,1')->post(
    '/webhooks/woocommerce/orders',
    [WoocommerceWebhookController::class, 'handle']
)->name('webhooks.woocommerce.orders');
