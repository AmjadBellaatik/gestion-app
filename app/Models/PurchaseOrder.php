<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class PurchaseOrder extends Model
{
    protected $fillable = [

        'company_id',

        'supplier_id',

        'warehouse_id',

        'order_number',

        'order_date',

        'status',

        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',

        'notes',

        'received_at',

    ];

    protected $casts = [

        'order_date' => 'date',

        'received_at' => 'datetime',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (session()->has('company_id')) {

                $model->company_id =
                    session('company_id');

            }

        });
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class
        );
    }

    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class
        );
    }

    public function items()
    {
        return $this->hasMany(
            PurchaseOrderItem::class
        );
    }
}