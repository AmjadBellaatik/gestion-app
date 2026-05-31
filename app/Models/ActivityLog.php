<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use SoftDeletes;

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