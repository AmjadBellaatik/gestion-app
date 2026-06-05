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

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->sales()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('remaining_amount');
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