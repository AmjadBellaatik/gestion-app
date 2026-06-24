<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'installment_plan_id',

        'due_date',

        'amount',

        'paid_amount',

        'status',

        'paid_at',

    ];

    public function plan()
    {
        return $this->belongsTo(
            InstallmentPlan::class,
            'installment_plan_id'
        );
    }
}