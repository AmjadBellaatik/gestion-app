<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Fund extends Model
{
    protected $fillable = [

        'company_id',

        'name',

        'type',

        'balance',

        'is_active',

        'notes',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

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