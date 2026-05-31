<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Setting extends Model
{
    protected $fillable = [

        'company_id',

        'group',

        'key',

        'value',

        'type',

        'is_public',

    ];

    protected $casts = [

        'is_public' => 'boolean',

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

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }
}