<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\RepairTicket;

use App\Models\Scopes\CompanyScope;

class Technician extends Model
{
    protected $fillable = [

        'company_id',

        'name',

        'phone',

        'speciality',

        'is_active',

    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (

                session()->has(
                    'company_id'
                )

            ) {

                $model->company_id =

                    session(
                        'company_id'
                    );

            }

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function repairTickets()
    {
        return $this->hasMany(
            RepairTicket::class
        );
    }
}