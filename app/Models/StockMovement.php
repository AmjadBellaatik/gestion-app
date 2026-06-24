<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\MotorcycleUnit;
use App\Models\Scopes\CompanyScope;
use App\Notifications\LowStockNotification;

class StockMovement extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'company_id',
        'product_id',
        'motorcycle_id',
        'motorcycle_unit_id',
        'warehouse_id',
        'user_id',
        'type',
        'movement_type',
        'reference',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'notes',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        /*
        |--------------------------------------------------------------------------
        | Warehouse Access Scope
        |--------------------------------------------------------------------------
        */

        static::addGlobalScope(
            'warehouse_access',

            function ($query) {

                $user = auth()->user();

                if (! $user) {

                    return;
                }

                if (

                    $user->hasRole(
                        'Super Admin'
                    )

                    ||

                    $user->hasRole(
                        'Admin'
                    )

                ) {

                    return;
                }

                $warehouseIds =

                    $user->warehouses

                        ->pluck('id');

                $query->whereIn(
                    'warehouse_id',
                    $warehouseIds
                );
            }
        );

        static::creating(function ($model) {

            if (session()->has('company_id')) {

                $model->company_id =
                    session('company_id');

            }

            if (auth()->check()) {

                $model->user_id =
                    auth()->id();

            }

        });

        static::created(function (StockMovement $model) {
            if (! $model->product_id) {
                return;
            }

            $product = Product::withoutGlobalScopes()->find($model->product_id);
            if (! $product || ! $product->stock_alert) {
                return;
            }

            $currentStock = $product->getCurrentStockAttribute();
            if ($currentStock > $product->stock_alert) {
                return;
            }

            $admins = User::role(['Admin', 'Super Admin'])->where('status', true)->get();
            $notification = new LowStockNotification($product, (int) $currentStock);

            $admins->each(function (User $admin) use ($notification) {
                try {
                    $admin->notify($notification);
                } catch (\Throwable) {
                    //
                }
            });
        });
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

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function motorcycleUnit()
    {
        return $this->belongsTo(
            MotorcycleUnit::class,
            'motorcycle_unit_id'
        );
    }

    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function referenceable()
    {
        return $this->morphTo(
            __FUNCTION__,
            'reference_type',
            'reference_id'
        );
    }
}
