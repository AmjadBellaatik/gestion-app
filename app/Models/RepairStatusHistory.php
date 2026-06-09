<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairStatusHistory extends Model
{
    protected $fillable = [
        'repair_ticket_id',
        'old_status',
        'new_status',
        'changed_by',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
