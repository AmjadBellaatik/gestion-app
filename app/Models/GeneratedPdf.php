<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $company_id
 * @property int $document_id
 * @property int|null $document_template_id
 * @property string $uuid
 * @property string $path
 * @property string $disk
 * @property int $template_version
 * @property string|null $checksum
 * @property \Carbon\Carbon|null $generated_at
 * @property int|null $generated_by
 */
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

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (GeneratedPdf $model) {
            $model->company_id ??= session('company_id');
        });
    }

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
