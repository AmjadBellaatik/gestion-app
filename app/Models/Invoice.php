<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;

class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'number',
        'client_name',
        'total',
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
}