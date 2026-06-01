<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Reimbursement extends Model
{
    protected $fillable = [

        'company_id',

        'repair_ticket_id',

        'warranty_claim_id',

        'supplier_id',

        'reference_number',

        'request_date',

        'expected_payment_date',

        'paid_date',

        'amount',

        'requested_amount',

        'approved_amount',

        'paid_amount',

        'status',

        'notes',

    ];

    protected $casts = [

        'request_date' => 'date',

        'expected_payment_date' => 'date',

        'paid_date' => 'date',

        'amount' => 'decimal:2',

        'requested_amount' => 'decimal:2',

        'approved_amount' => 'decimal:2',

        'paid_amount' => 'decimal:2',

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

    public function warrantyClaim()
    {
        return $this->belongsTo(
            WarrantyClaim::class
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
