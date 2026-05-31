<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [

        'purchase_order_id',

        'product_id',

        'description',

        'quantity',

        'unit_price',

        'tax_rate',
        'tax_amount',

        'discount_amount',

        'total',

    ];

    protected $casts = [

        'quantity' => 'decimal:2',

        'unit_price' => 'decimal:2',

        'tax_rate' => 'decimal:2',

        'tax_amount' => 'decimal:2',

        'discount_amount' => 'decimal:2',

        'total' => 'decimal:2',

    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(
            PurchaseOrder::class
        );
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }
}