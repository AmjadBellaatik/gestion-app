<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class TreasuryTransaction extends Model
{
    protected $fillable = [

        'company_id',

        'cash_register_id',

        'payment_id',

        'type',

        'amount',

        'description',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (

                session()->has('company_id')

                &&

                ! $model->company_id

            ) {

                $model->company_id =
                    session('company_id');

            }

        });
    }

    public function payment()
    {
        return $this->belongsTo(
            Payment::class
        );
    }

    public function cashRegister()
    {
        return $this->belongsTo(
            CashRegister::class
        );
    }
}