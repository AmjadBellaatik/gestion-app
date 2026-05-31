<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Brand extends Model
{
    protected $fillable = [

        'company_id',

        'name',
        'accreditation_reference',

        'logo',
        'logo_dark',
        'logo_light',

        'pdf_header',
        'pdf_footer',

        'stamp',
        'stamp_image',

        'signature',
        'director_signature',
        'footer',

        'legal_notice',

        'invoice_terms',
        'warranty_terms',

        'primary_color',
        'secondary_color',

        'color_palette',

        'qr_code',

        'is_active',

    ];

    protected $casts = [

        'color_palette' => 'array',

        'is_active' => 'boolean',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (session()->has('company_id') && ! $model->company_id) {

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

    public function documents()
    {
        return $this->hasMany(
            Document::class
        );
    }

    public function documentTemplates()
    {
        return $this->hasMany(
            DocumentTemplate::class
        );
    }

    public function motorcycleModels()
    {
        return $this->hasMany(MotorcycleModel::class);
    }
}
