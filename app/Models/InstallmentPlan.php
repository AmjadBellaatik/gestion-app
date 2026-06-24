<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class InstallmentPlan extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'company_id',

        'client_id',

        'document_id',

        'total_amount',

        'paid_amount',

        'remaining_amount',

        'months',

        'start_date',

        'status',

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

        });
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

    public function installments()
    {
        return $this->hasMany(
            Installment::class
        );
    }
}