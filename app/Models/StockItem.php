<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'stock_items';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function motorcycleModel()
    {
        return $this->belongsTo(MotorcycleModel::class);
    }

    public function getLiveQuantityAttribute(): float
    {
        if ($this->item_kind === 'motorcycle_model') {
            return (float) MotorcycleUnit::query()
                ->where('motorcycle_model_id', $this->motorcycle_model_id)
                ->whereIn('status', ['available', 'in_stock'])
                ->count();
        }

        return (float) ($this->product?->current_stock ?? 0);
    }

    public function getIsLowStockAttribute(): bool
    {
        $quantity = (float) $this->live_quantity;
        $alert = (float) $this->stock_alert;

        return $quantity > 0 && $alert > 0 && $quantity <= $alert;
    }
}
