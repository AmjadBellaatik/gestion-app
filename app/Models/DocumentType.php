<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $code
 * @property string|null $prefix
 * @property string|null $category
 * @property string|null $template
 * @property string|null $blade_view
 * @property array|null $automatic_variables
 * @property bool $header_enabled
 * @property bool $footer_enabled
 * @property bool $affects_stock
 * @property bool $affects_accounting
 * @property string|null $default_language
 * @property string|null $language
 * @property bool $is_active
 */
class DocumentType extends Model
{
    public const INVOICE = 'INVOICE';
    public const QUOTATION = 'QUOTE';
    public const DELIVERY_NOTE = 'DELIVERY_NOTE';
    public const SUPPLIER_ORDER = 'PURCHASE_ORDER';
    public const WARRANTY_CONTRACT = 'WARRANTY_CONTRACT';
    public const CONFORMITY = 'CONFORMITY';
    public const OWNERSHIP = 'OWNERSHIP';
    public const SALE_RETURN = 'SALE_RETURN';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'prefix',
        'category',
        'template',
        'blade_view',
        'automatic_variables',
        'header_enabled',
        'footer_enabled',
        'affects_stock',
        'affects_accounting',
        'default_language',
        'language',
        'is_active',
    ];

    protected $casts = [
        'automatic_variables' => 'array',
        'header_enabled' => 'boolean',
        'footer_enabled' => 'boolean',
        'affects_stock' => 'boolean',
        'affects_accounting' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (DocumentType $type) {
            $type->default_language ??= 'fr';
            $type->language ??= 'fr';
            $type->category ??= 'commercial';
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    public function defaultBladeView(): string
    {
        return $this->blade_view ?: match ($this->code) {
            self::INVOICE => 'documents.pdf.commercial-invoice',
            self::QUOTATION => 'documents.pdf.commercial-quotation',
            self::DELIVERY_NOTE => 'documents.pdf.delivery-note',
            self::SUPPLIER_ORDER => 'documents.pdf.supplier-order',
            self::WARRANTY_CONTRACT => 'documents.pdf.warranty-contract',
            self::CONFORMITY => 'documents.pdf.conformity-certificate',
            self::OWNERSHIP => 'documents.pdf.ownership-prsk',
            self::SALE_RETURN => 'documents.pdf.sale-return',
            default => 'documents.pdf.generic-document',
        };
    }
}
