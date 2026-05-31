<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedPdf extends Model
{
    protected $fillable = [
        'company_id',
        'document_id',
        'document_template_id',
        'uuid',
        'path',
        'disk',
        'template_version',
        'checksum',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'template_version' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }
}
