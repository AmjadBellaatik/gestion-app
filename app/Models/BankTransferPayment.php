<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransferPayment extends Model
{
    public const STATUSES = [
        'sent'     => 'sent',
        'received' => 'received',
    ];

    protected $fillable = [
        'payment_id',
        'bank_name',
        'reference_number',
        'transfer_date',
        'confirmation_file',
        'status',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
