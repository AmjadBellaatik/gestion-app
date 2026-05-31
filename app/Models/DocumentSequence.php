<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class DocumentSequence extends Model
{
    protected $fillable = [

        'company_id',
        'brand_id',
        'document_type_id',

        'year',

        'prefix',

        'current_number',

        'padding',

        'yearly_reset',

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

    public function documentType()
    {
        return $this->belongsTo(
            DocumentType::class
        );
    }
}