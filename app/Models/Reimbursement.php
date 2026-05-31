<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Reimbursement extends Model
{
    protected $fillable = [

        'company_id',

        'repair_ticket_id',

        'supplier_id',

        'amount',

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

    public function repairTicket()
    {
        return $this->belongsTo(
            RepairTicket::class
        );
    }

    public function supplier()
    {
        return $this->belongsTo(

            Client::class,

            'supplier_id'

        );
    }
}