<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Warranty extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'sale_id',
        'motorcycle_id',
        'motorcycle_unit_id',
        'product_id',
        'start_date',
        'end_date',
        'status',
        'warranty_kilometers',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (session()->has('company_id')) {
                $model->company_id = session('company_id');
            }
        });
    }

    // Auto-derive status from end_date — no manual status needed.
    public function getStatusAttribute(): string
    {
        if ($this->end_date && Carbon::parse($this->end_date)->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    // Readable label: chassis number for motorcycle units, SKU for products.
    public function getItemLabelAttribute(): string
    {
        if ($this->motorcycleUnit) {
            return $this->motorcycleUnit->chassis_number ?? ('Unit #' . $this->motorcycle_unit_id);
        }

        if ($this->product) {
            return ($this->product->name ?? '') . ($this->product->sku ? ' — ' . $this->product->sku : '');
        }

        return '—';
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function motorcycleUnit()
    {
        return $this->belongsTo(MotorcycleUnit::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function motorcycle()
    {
        return $this->belongsTo(Product::class, 'motorcycle_id');
    }
}
