<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    protected $fillable = [

        'journal_entry_id',

        'account_code',

        'debit',

        'credit',

    ];

    public function entry()
    {
        return $this->belongsTo(
            JournalEntry::class,
            'journal_entry_id'
        );
    }
}