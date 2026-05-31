<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class StockTransfer extends Model
{
    protected $fillable = [

        'company_id',

        'from_warehouse_id',

        'to_warehouse_id',

        'created_by',

        'reference',

        'status',

        'notes',

    ];

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

            /*
            |--------------------------------------------------------------------------
            | Created By
            |--------------------------------------------------------------------------
            */

            $model->created_by =
                auth()->id();

            if (

                empty(
                    $model->reference
                )

            ) {

                $model->reference =

                    'TRF-' .

                    now()->format('YmdHis');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function fromWarehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'from_warehouse_id'
        );
    }

    public function toWarehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'to_warehouse_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function items()
    {
        return $this->hasMany(
            StockTransferItem::class
        );
    }
}