<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Sale;
use App\Models\RepairTicket;
use App\Models\Document;

class Client extends Model
{
    protected $fillable = [

        'company_id',

        'reseller_id',

        'client_type',

        'first_name',
        'last_name',

        'company_name',

        'administration_name',

        'phone',
        'email',
        'address',

        'city',
        'country',

        'cin',
        'birth_date',
        'nationality',

        'ice',
        'rc',
        'if',
        'patente',

        'representative_name',

        'department',
        'responsible_person',

        'notes',

        'balance',

        'is_active',

        'is_blocked',

        'blocked_reason',

    ];

    protected $casts = [

        'birth_date' => 'date',

        'balance' => 'decimal:2',

        'is_active' => 'boolean',

        'is_blocked' => 'boolean',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (
                auth()->check()
                &&
                empty($model->company_id)
            ) {

                $model->company_id =
                    session('company_id');
            }
        });

        static::saving(function ($model) {
            if ($model->is_blocked) {
                $model->is_active = false;
            } elseif ($model->isDirty('is_blocked') && ! $model->is_blocked && $model->getOriginal('is_blocked')) {
                $model->is_active = true;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(
            Reseller::class
        );
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function repairTickets(): HasMany
    {
        return $this->hasMany(RepairTicket::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(\App\Models\Warranty::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        return match ($this->client_type) {

            'company' =>

                $this->company_name
                    ?? '',

            'administration' =>

                $this->administration_name
                    ?? '',

            default => trim(

                ($this->first_name ?? '') . ' ' .

                ($this->last_name ?? '')

            ),
        };
    }

    /**
     * Live outstanding balance — the single source of truth for what a client owes.
     *
     * Sums remaining_amount across the client's non-deleted sales that are still
     * unpaid or partially paid. Because it reads the sales() relationship, it
     * automatically excludes soft-deleted sales and respects CompanyScope.
     *
     * When the query eager-loads the aggregate via withOutstandingBalance()
     * (see scope below), that pre-computed value is used to avoid an N+1 query;
     * otherwise it falls back to a live aggregate. Both paths use the identical
     * formula, so list and detail views can never diverge.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        if (array_key_exists('outstanding_balance_sum', $this->attributes)) {
            return (float) $this->attributes['outstanding_balance_sum'];
        }

        return (float) $this->sales()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('remaining_amount');
    }

    /**
     * Eager-load the outstanding balance as a SQL aggregate (single query),
     * exposed to the accessor as `outstanding_balance_sum`. Use this in list
     * queries so every row's balance comes from the same formula as the
     * detail page without triggering N+1 queries.
     */
    public function scopeWithOutstandingBalance(Builder $query): Builder
    {
        return $query->withSum(
            ['sales as outstanding_balance_sum' => fn (Builder $sub) => $sub
                ->whereIn('payment_status', ['unpaid', 'partial'])],
            'remaining_amount'
        );
    }

    /**
     * Number of days after which an unpaid/partial sale is considered overdue.
     * Sales have no explicit due_date, so accounting uses an aging threshold
     * from the sale creation date. Adjust here if a due_date is introduced.
     */
    public const OVERDUE_DAYS = 30;

    /**
     * Eager-load EVERY accounting figure in a single query for the Client
     * Balances module. All money figures derive from the sales table (so they
     * always reconcile: total = paid + remaining per sale) and share the exact
     * same outstanding-balance definition as the detail and list pages.
     *
     * Exposed aggregate attributes:
     *   - outstanding_balance_sum   SUM(remaining) on unpaid/partial sales   (= source of truth)
     *   - total_sales_sum           SUM(total) of all sales
     *   - total_payments_sum        SUM(paid_amount) of all sales
     *   - credit_balance_sum        SUM(paid_amount - total) where overpaid (client credit)
     *   - open_sales_count          COUNT of unpaid/partial sales
     *   - overdue_sales_count       COUNT of unpaid/partial sales older than OVERDUE_DAYS
     *   - overdue_amount_sum        SUM(remaining) of those overdue sales
     *   - last_sale_at              MAX(sales.created_at)
     *   - last_payment_at           MAX(payments.created_at) for validated payments
     */
    public function scopeWithAccountingAggregates(Builder $query): Builder
    {
        $overdueCutoff = now()->subDays(self::OVERDUE_DAYS);

        return $query
            // Outstanding (THE source of truth — identical formula everywhere)
            ->withSum(
                ['sales as outstanding_balance_sum' => fn (Builder $q) => $q
                    ->whereIn('payment_status', ['unpaid', 'partial'])],
                'remaining_amount'
            )
            ->withSum('sales as total_sales_sum', 'total')
            ->withSum('sales as total_payments_sum', 'paid_amount')
            ->withCount(['sales as open_sales_count' => fn (Builder $q) => $q
                ->whereIn('payment_status', ['unpaid', 'partial'])])
            ->withCount(['sales as overdue_sales_count' => fn (Builder $q) => $q
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereDate('sale_date', '<', $overdueCutoff)])
            ->withSum(
                ['sales as overdue_amount_sum' => fn (Builder $q) => $q
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->whereDate('sale_date', '<', $overdueCutoff)],
                'remaining_amount'
            )
            ->withMax('sales as last_sale_at', 'sale_date')
            // Client credit (overpayment) — GREATEST not portable in withSum, use subquery
            ->selectSub(
                Sale::query()
                    ->withoutGlobalScopes()
                    ->selectRaw('COALESCE(SUM(GREATEST(paid_amount - total, 0)), 0)')
                    ->whereColumn('sales.client_id', 'clients.id')
                    ->whereNull('sales.deleted_at'),
                'credit_balance_sum'
            )
            ->selectSub(
                \App\Models\Payment::query()
                    ->withoutGlobalScopes()
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('payments.client_id', 'clients.id')
                    ->where('status', 'paid')
                    ->whereNull('payments.deleted_at'),
                'last_payment_at'
            );
    }

    /* Convenience accessors over the eager-loaded aggregates (default 0/null). */
    public function getTotalSalesAttribute(): float
    {
        return (float) ($this->attributes['total_sales_sum'] ?? 0);
    }

    public function getTotalPaymentsAttribute(): float
    {
        return (float) ($this->attributes['total_payments_sum'] ?? 0);
    }

    public function getCreditBalanceAttribute(): float
    {
        return (float) ($this->attributes['credit_balance_sum'] ?? 0);
    }

    public function getCurrentDebtAttribute(): float
    {
        // Current debt IS the outstanding balance (what the client owes now).
        return $this->outstanding_balance;
    }

    public function getOverdueAmountAttribute(): float
    {
        return (float) ($this->attributes['overdue_amount_sum'] ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {

        return $query->where(
            'is_active',
            true
        );
    }
}