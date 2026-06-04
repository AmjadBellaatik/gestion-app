<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{

    protected $fillable = [

        'company_id',

        'user_id',

        'module',

        'action',

        'model_type',

        'model_id',

        'description',

        'old_values',

        'new_values',

        'ip_address',

        'user_agent',

    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (ActivityLog $model) {
            $model->company_id ??= session('company_id');
        });
    }

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