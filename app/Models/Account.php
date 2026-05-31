<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Account extends Model
{
    protected $fillable = [

        'company_id',

        'name',
        'code',
        'type',

        'balance',

        'is_active',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function (
            $model
        ) {

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