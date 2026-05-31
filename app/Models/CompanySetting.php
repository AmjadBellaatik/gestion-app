<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class CompanySetting extends Model
{
    protected $fillable = [

        'company_id',

        'logo',

        'stamp',

        'signature',

        'footer',

        'invoice_prefix',

        'quotation_prefix',

        'repair_prefix',

        'default_language',

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