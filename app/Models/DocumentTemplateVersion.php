<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateVersion extends Model
{
    protected $fillable = [
        'document_template_id',
        'version',
        'blade_view',
        'variables',
        'header_config',
        'footer_config',
        'created_by_name',
    ];

    protected $casts = [
        'version' => 'integer',
        'variables' => 'array',
        'header_config' => 'array',
        'footer_config' => 'array',
    ];

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }
}
