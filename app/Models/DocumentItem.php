<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'item_type',
        'product_id',
        'motorcycle_id',
        'motorcycle_unit_id',
        'warehouse_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'total',
        'serial_number',
        'warranty_months',
        'line_notes',
        'line_sort',
        'unit_type',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (DocumentItem $item) {
            $item->tax_rate = Document::TAX_RATE;

            $totalIncludingTax = max(
                ((float) $item->quantity * (float) $item->unit_price) - (float) $item->discount_amount,
                0
            );

            $item->tax_amount = round($totalIncludingTax * (Document::TAX_RATE / (100 + Document::TAX_RATE)), 2);
            $item->tax = $item->tax_amount;
            $item->total = round($totalIncludingTax, 2);
            $item->unit_type ??= 'unit';

            if (! $item->description) {
                $item->description = $item->product?->name
                    ?? $item->motorcycleUnit?->motorcycleModel?->modele
                    ?? $item->motorcycleUnit?->chassis_number;
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function motorcycleUnit(): BelongsTo
    {
        return $this->belongsTo(MotorcycleUnit::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
