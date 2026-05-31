<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WoocommerceOrder extends Model
{
    protected $table = 'woocommerce_orders';

    protected $fillable = [
        'wc_order_id',
        'wc_order_number',
        'status',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'customer_phone',
        'billing',
        'shipping',
        'line_items',
        'shipping_lines',
        'fee_lines',
        'discount_total',
        'shipping_total',
        'total',
        'currency',
        'payment_method',
        'payment_method_title',
        'paid',
        'customer_note',
        'raw_payload',
        'ordered_at',
    ];

    protected $casts = [
        'billing'        => 'array',
        'shipping'       => 'array',
        'line_items'     => 'array',
        'shipping_lines' => 'array',
        'fee_lines'      => 'array',
        'raw_payload'    => 'array',
        'paid'           => 'boolean',
        'discount_total' => 'float',
        'shipping_total' => 'float',
        'total'          => 'float',
        'ordered_at'     => 'datetime',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getCustomerNameAttribute(): string
    {
        return trim(($this->customer_first_name ?? '') . ' ' . ($this->customer_last_name ?? ''));
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed'  => 'success',
            'processing' => 'warning',
            'pending'    => 'info',
            'on-hold'    => 'warning',
            'cancelled'  => 'danger',
            'refunded'   => 'danger',
            'failed'     => 'danger',
            default      => 'gray',
        };
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Build a WoocommerceOrder from a raw WooCommerce webhook payload array.
     */
    public static function fromPayload(array $data): array
    {
        $billing  = $data['billing']  ?? [];
        $shipping = $data['shipping'] ?? [];

        $lineItems = collect($data['line_items'] ?? [])->map(fn ($item) => [
            'id'        => $item['id']        ?? null,
            'name'      => $item['name']      ?? '',
            'sku'       => $item['sku']       ?? '',
            'quantity'  => $item['quantity']  ?? 1,
            'subtotal'  => $item['subtotal']  ?? 0,
            'total'     => $item['total']     ?? 0,
            'price'     => round((float)($item['total'] ?? 0) / max(1, (int)($item['quantity'] ?? 1)), 2),
        ])->values()->all();

        $shippingLines = collect($data['shipping_lines'] ?? [])->map(fn ($s) => [
            'method_title' => $s['method_title'] ?? '',
            'total'        => $s['total']        ?? 0,
        ])->values()->all();

        $feelines = collect($data['fee_lines'] ?? [])->map(fn ($f) => [
            'name'  => $f['name']  ?? '',
            'total' => $f['total'] ?? 0,
        ])->values()->all();

        $orderedAt = null;
        if (! empty($data['date_created'])) {
            try { $orderedAt = \Carbon\Carbon::parse($data['date_created']); } catch (\Exception) {}
        }

        return [
            'wc_order_id'          => $data['id'],
            'wc_order_number'      => '#' . ($data['number'] ?? $data['id']),
            'status'               => $data['status'] ?? 'pending',
            'customer_first_name'  => $billing['first_name'] ?? ($data['billing']['first_name'] ?? null),
            'customer_last_name'   => $billing['last_name']  ?? ($data['billing']['last_name']  ?? null),
            'customer_email'       => $billing['email']      ?? ($data['billing']['email']      ?? null),
            'customer_phone'       => $billing['phone']      ?? ($data['billing']['phone']      ?? null),
            'billing'              => $billing,
            'shipping'             => $shipping,
            'line_items'           => $lineItems,
            'shipping_lines'       => $shippingLines,
            'fee_lines'            => $feelines,
            'discount_total'       => (float) ($data['discount_total'] ?? 0),
            'shipping_total'       => (float) ($data['shipping_total'] ?? 0),
            'total'                => (float) ($data['total']          ?? 0),
            'currency'             => $data['currency'] ?? 'MAD',
            'payment_method'       => $data['payment_method']       ?? null,
            'payment_method_title' => $data['payment_method_title'] ?? null,
            'paid'                 => ! empty($data['date_paid']),
            'customer_note'        => $data['customer_note'] ?? null,
            'raw_payload'          => $data,
            'ordered_at'           => $orderedAt,
        ];
    }
}
