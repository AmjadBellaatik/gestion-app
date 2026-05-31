<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class ConformityCertificate extends Model
{
    protected $fillable = [

        'company_id',

        'brand_id',

        'motorcycle_id',

        'document_id',

        'certificate_number',

        'homologation_reference',

        'vin_number',

        'engine_number',

        'issued_at',

        'is_locked',

        'is_validated',

        'validated_at',

        'validated_by',

        'official_wording',

        'hash',

    ];

    protected $casts = [

        'issued_at' => 'date',

        'validated_at' => 'datetime',

        'is_locked' => 'boolean',

        'is_validated' => 'boolean',

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

            if ($model->motorcycle) {

                $model->vin_number =
                    $model->motorcycle->vin_number;

                $model->engine_number =
                    $model->motorcycle->engine_number;

            }

        });

        static::updating(function ($model) {

            if ($model->is_locked) {

                unset(

                    $model->vin_number,

                    $model->engine_number,

                    $model->homologation_reference

                );

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

    public function validator()
    {
        return $this->belongsTo(
            User::class,
            'validated_by'
        );
    }
}