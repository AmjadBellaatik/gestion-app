<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class MotorcycleUnit extends Model
{
    protected $fillable = [

        'company_id',

        'warehouse_id',

        'motorcycle_model_id',

        'client_id',

        'document_id',

        'chassis_number',

        'fabrication_number',

        'mileage',

        'status',

        'purchase_date',

        'sale_date',

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

            if (

                session()->has('company_id')

                &&

                ! $model->company_id

            ) {

                $model->company_id =
                    session('company_id');

            }

            $model->mileage ??= 0;

        });

        static::saving(function ($model) {
            $model->mileage ??= 0;
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

    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class
        );
    }

    public function motorcycleModel()
    {
        return $this->belongsTo(
            MotorcycleModel::class
        );
    }

    public function client()
    {
        return $this->belongsTo(
            Client::class
        );
    }

    public function document()
    {
        return $this->belongsTo(
            Document::class
        );
    }
}
