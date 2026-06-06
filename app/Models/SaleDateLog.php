<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit record of a sale_date change.
 * One row per modification — never updated or silently overwritten.
 */
class SaleDateLog extends Model
{
    protected $fillable = [
        'company_id',
        'sale_id',
        'user_id',
        'user_name',
        'old_date',
        'new_date',
        'changed_at',
    ];

    protected $casts = [
        'old_date'   => 'date',
        'new_date'   => 'date',
        'changed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
