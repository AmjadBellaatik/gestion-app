<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'brand_id',
        'document_type_id',
        'name',
        'category',
        'blade_view',
        'version',
        'variables',
        'header_config',
        'footer_config',
        'language',
        'is_default',
        'orientation',
        'paper_size',
        'rtl',
        'footer_enabled',
        'header_enabled',
        'watermark',
        'signature_enabled',
        'stamp_enabled',
        'template_type',
    ];

    protected $casts = [
        'version' => 'integer',
        'variables' => 'array',
        'header_config' => 'array',
        'footer_config' => 'array',
        'is_default' => 'boolean',
        'rtl' => 'boolean',
        'footer_enabled' => 'boolean',
        'header_enabled' => 'boolean',
        'signature_enabled' => 'boolean',
        'stamp_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (DocumentTemplate $template) {
            $template->company_id ??= session('company_id');
            $template->language ??= 'fr';
            $template->category ??= 'commercial';
            $template->version ??= 1;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class);
    }
}
