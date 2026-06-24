<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class RepairType extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'company_id',

        'name',

        'code',

        'color',

        'affects_warranty',

        'billable',

        'active',

        'description',

    ];

    protected $casts = [

        'affects_warranty' => 'boolean',

        'billable' => 'boolean',

        'active' => 'boolean',

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

    public function repairTickets()
    {
        return $this->hasMany(
            RepairTicket::class
        );
    }
}