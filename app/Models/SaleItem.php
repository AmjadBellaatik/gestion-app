<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [

        'sale_id',
        'product_id',
        'motorcycle_unit_id',
        'motorcycle_id',
        'quantity',
        'returned_quantity',
        'unit_price',
        'discount',
        'tax',
        'total',
        'warranty_duration_value',
        'warranty_duration_unit',
        'warranty_kilometers',

    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'warranty_duration_value' => 'integer',
        'warranty_kilometers' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function sale()
    {
        return $this->belongsTo(
            Sale::class
        );
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function motorcycle()
    {
        return $this->belongsTo(
            Motorcycle::class
        );
    }

    public function motorcycleUnit()
    {
        return $this->belongsTo(
            MotorcycleUnit::class
        );
    }
}
