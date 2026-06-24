<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class WarrantyContract extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'company_id',

        'brand_id',

        'client_id',

        'motorcycle_id',

        'document_id',

        'contract_number',

        'delivery_date',

        'expiration_date',

        'mileage_limit',

        'current_mileage',

        'warranty_terms',

        'warranty_exclusions',

        'customer_signed',
        'customer_signed_at',

        'seller_signed',
        'seller_signed_at',

        'customer_signature',
        'seller_signature',

        'status',

    ];

    protected $casts = [

        'delivery_date' => 'date',

        'expiration_date' => 'date',

        'customer_signed' => 'boolean',

        'seller_signed' => 'boolean',

        'customer_signed_at' => 'datetime',

        'seller_signed_at' => 'datetime',

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

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function brand()
    {
        return $this->belongsTo(
            Brand::class
        );
    }

    public function client()
    {
        return $this->belongsTo(
            Client::class
        );
    }

    public function motorcycle()
    {
        return $this->belongsTo(
            Motorcycle::class
        );
    }

    public function document()
    {
        return $this->belongsTo(
            Document::class
        );
    }

    public function claims()
    {
        return $this->hasMany(
            WarrantyClaim::class
        );
    }

    public function repairTickets()
    {
        return $this->hasManyThrough(

            RepairTicket::class,

            WarrantyClaim::class,

            'warranty_contract_id',

            'id',

            'id',

            'repair_ticket_id'

        );
    }

    public function isExpired(): bool
    {
        return now()->gt(
            $this->expiration_date
        );
    }

    public function exceedsMileage(): bool
    {
        if (! $this->mileage_limit) {

            return false;
        }

        return

            $this->current_mileage >

            $this->mileage_limit;
    }

    public function isValid(): bool
    {
        return

            ! $this->isExpired()

            &&

            ! $this->exceedsMileage()

            &&

            $this->status === 'active';
    }
}