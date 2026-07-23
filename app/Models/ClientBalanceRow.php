<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only model backed by the `client_balance_rows` SQL view (see its
 * migration for the exact query) — one row per Client OR Reseller, unified
 * for the "Soldes clients" accounting page. `source_type` + `source_id`
 * identify the real underlying Client/Reseller record; `id` is only a
 * synthetic string key ("client-5" / "reseller-12") so both sets of rows
 * can coexist in one Filament table without colliding.
 *
 * Never call save()/delete() on this model — the view has no writable
 * columns and no primary key the database recognizes as such.
 */
class ClientBalanceRow extends Model
{
    protected $table = 'client_balance_rows';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'source_id'               => 'integer',
        'company_id'               => 'integer',
        'is_active'                => 'boolean',
        'is_blocked'                => 'boolean',
        'created_at'                => 'datetime',
        'last_sale_at'              => 'date',
        'last_payment_at'           => 'datetime',
        'total_sales_sum'           => 'decimal:2',
        'total_payments_sum'        => 'decimal:2',
        'outstanding_balance_sum'   => 'decimal:2',
        'credit_balance_sum'        => 'decimal:2',
        'open_sales_count'          => 'integer',
        'overdue_sales_count'       => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function ($query) {
            if (session()->has('company_id')) {
                $query->where('company_id', session('company_id'));
            }
        });
    }

    public function isReseller(): bool
    {
        return $this->source_type === 'reseller';
    }

    /* Convenience accessors mirroring Client's own naming, since the view
     * already computes these as plain columns (no live aggregation needed). */

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->outstanding_balance_sum;
    }

    public function getTotalSalesAttribute(): float
    {
        return (float) $this->total_sales_sum;
    }

    public function getTotalPaymentsAttribute(): float
    {
        return (float) $this->total_payments_sum;
    }

    public function getCreditBalanceAttribute(): float
    {
        return (float) $this->credit_balance_sum;
    }
}
