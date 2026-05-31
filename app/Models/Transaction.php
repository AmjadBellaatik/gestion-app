<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Transaction extends Model
{
    protected $fillable = [

        'company_id',

        'type',

        'category',

        'amount',

        'direction',

        'reference_type',

        'reference_id',

        'payment_method',

        'status',

        'description',

        'transaction_date',

        'user_id',

        'created_by',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function (self $model) {

            if (

                session()->has(
                    'company_id'
                )

            ) {

                $model->company_id =

                    session(
                        'company_id'
                    );

            }

        });
    }
}