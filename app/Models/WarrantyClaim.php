<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class WarrantyClaim extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'company_id',

        'warranty_contract_id',

        'repair_ticket_id',

        'motorcycle_id',

        'client_id',

        'claim_number',

        'claim_date',

        'status',

        'issue_description',

        'diagnosis',

        'claimed_amount',

        'approved_amount',

        'reimbursed_amount',

        'approved',

        'approved_at',

        'reimbursed_at',

        'notes',

    ];

    protected $casts = [

        'claim_date' => 'date',

        'approved_at' => 'datetime',

        'reimbursed_at' => 'datetime',

        'approved' => 'boolean',

        'claimed_amount' => 'decimal:2',

        'approved_amount' => 'decimal:2',

        'reimbursed_amount' => 'decimal:2',

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

            if (! $model->claim_number) {

                $model->claim_number =

                    'WC-' .

                    now()->year .

                    '-' .

                    str_pad(

                        static::count() + 1,

                        5,

                        '0',

                        STR_PAD_LEFT

                    );
            }

        });
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function warrantyContract()
    {
        return $this->belongsTo(
            WarrantyContract::class
        );
    }

    public function repairTicket()
    {
        return $this->belongsTo(
            RepairTicket::class
        );
    }

    public function motorcycle()
    {
        return $this->belongsTo(
            Motorcycle::class
        );
    }

    public function client()
    {
        return $this->belongsTo(
            Client::class
        );
    }

    public function reimbursements()
    {
        return $this->hasMany(
            Reimbursement::class
        );
    }
}