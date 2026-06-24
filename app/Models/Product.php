<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

use App\Models\StockMovement;

use App\Models\Scopes\CompanyScope;

class Product extends Model
{
    use \App\Models\Concerns\Auditable;

    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Product Types
    |--------------------------------------------------------------------------
    */

    public const TYPES = [

        'part',

        'accessory',

        'trotinette',

        'velo_electrique',

        'velo_normal',

        'consumable',

    ];

    protected $fillable = [

        'company_id',

        'name',

        'barcode',

        'type',

        'purchase_price',

        'selling_price',

        'reseller_price',

        'stock_alert',
        'status',

        'serial_required',
        'has_warranty',

    ];

    protected $casts = [

        'serial_required' => 'boolean',
        'has_warranty' => 'boolean',
        'status' => 'string',

        'purchase_price' => 'decimal:2',

        'selling_price' => 'decimal:2',

        'reseller_price' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (

                session()->has('company_id')

                &&

                ! $model->company_id

            ) {

                $model->company_id =
                    session('company_id');

            }

            if (blank($model->sku)) {
                $model->sku = self::generateSku();
            }

        });

        static::updating(function (Product $model) {
            if ($model->isDirty('sku')) {
                $model->sku = $model->getOriginal('sku');
            }
        });
    }

    public static function generateSku(): string
    {
        do {
            $sku = 'PRD-' . Str::upper(Str::random(8));
        } while (self::query()->where('sku', $sku)->exists());

        return $sku;
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function stockMovements()
    {
        return $this->hasMany(
            StockMovement::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getCurrentStockAttribute()
    {
        $in = $this->stockMovements()
            ->withoutGlobalScope('warehouse_access')
            ->where(function ($query): void {
                $query
                    ->whereIn('type', [
                        'entry',
                        'in',
                        'transfer',
                        'adjustment',
                        'return',
                    ])
                    ->orWhereIn('movement_type', [
                        'purchase',
                        'return',
                    ]);
            })
            ->sum('quantity');

        $out = $this->stockMovements()
            ->withoutGlobalScope('warehouse_access')
            ->where(function ($query): void {
                $query
                    ->whereIn('type', [
                        'exit',
                        'out',
                    ])
                    ->orWhereIn('movement_type', [
                        'sale',
                        'repair',
                        'repair_usage',
                    ]);
            })
            ->sum('quantity');

        return $in - $out;
    }
}
