<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'stock_transfer_id',

        'product_id',

        'motorcycle_unit_id',

        'quantity',

    ];

    public function transfer()
    {
        return $this->belongsTo(
            StockTransfer::class,
            'stock_transfer_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function motorcycleUnit()
    {
        return $this->belongsTo(
            MotorcycleUnit::class
        );
    }
}