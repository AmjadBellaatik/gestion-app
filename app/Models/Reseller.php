<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use App\Models\Scopes\CompanyScope;

class Reseller extends Model
{
    protected $fillable = [

        'company_id',

        'name',

        'phone',

        'email',

        'address',

        'city',

        'country',

        /*
        |--------------------------------------------------------------------------
        | Legal Information
        |--------------------------------------------------------------------------
        */

        'ice',

        'rc',

        'if',

        'patente',

        'representative_name',

        /*
        |--------------------------------------------------------------------------
        | Financial
        |--------------------------------------------------------------------------
        */

        'credit_balance',

        'current_debt',

        'max_debt',

        'credit_days',

        'total_orders',

        'total_paid',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'is_blocked',

        'blocked_reason',

        'is_active',

        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        'notes',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (session()->has('company_id')) {

                $model->company_id =
                    session('company_id');

            }

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Sale::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-calculated Financial Fields
    |--------------------------------------------------------------------------
    */

    public function recalculate(): void
    {
        $saleIds = $this->sales()->pluck('id');

        $totalOrders = $this->sales()->sum('total');

        $totalPaid = Payment::withoutGlobalScopes()
            ->whereIn('sale_id', $saleIds)
            ->where('status', 'paid')
            ->sum('amount');

        $this->updateQuietly([
            'total_orders' => $totalOrders,
            'total_paid'   => $totalPaid,
            'current_debt' => max(0, $totalOrders - $totalPaid),
        ]);
    }
}