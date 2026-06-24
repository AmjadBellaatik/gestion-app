<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $sale_id
 * @property int|null $product_id
 * @property int|null $motorcycle_unit_id
 * @property int|null $motorcycle_id
 * @property float $quantity
 * @property float $returned_quantity
 * @property float $unit_price
 * @property float $discount
 * @property float $tax
 * @property float $total
 * @property int|null $warranty_duration_value
 * @property string|null $warranty_duration_unit
 * @property int|null $warranty_kilometers
 */
class SaleItem extends Model
{
    use \App\Models\Concerns\Auditable;

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
