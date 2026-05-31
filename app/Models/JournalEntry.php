<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class JournalEntry extends Model
{
    protected $fillable = [

        'company_id',

        'date',

        'reference',

        'description',

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

    public function lines()
    {
        return $this->hasMany(
            JournalEntryLine::class
        );
    }
}