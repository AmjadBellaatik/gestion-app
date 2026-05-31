<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class LoginLog extends Model
{
    protected $fillable = [

        'company_id',

        'user_id',

        'email',

        'ip_address',

        'user_agent',

        'logged_in_at',

    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function (
            $model
        ) {

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
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }
}