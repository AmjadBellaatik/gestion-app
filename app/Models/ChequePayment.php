<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequePayment extends Model
{
    public const STATUSES = [
        'received' => 'received',
        'paid'     => 'paid',
        'bounced'  => 'bounced',
    ];

    protected $fillable = [
        'payment_id',
        'cheque_number',
        'bank_name',
        'due_date',
        'scan_path',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
