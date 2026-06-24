<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotorcycleHomologation extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [
        'motorcycle_model_id',
        'homologation_number',
        'homologation_date',
        'manufacturer',
        'country',
        'technical_data',
        'source_document_path',
    ];

    protected $casts = [
        'homologation_date' => 'date',
        'technical_data' => 'array',
    ];

    public function motorcycleModel(): BelongsTo
    {
        return $this->belongsTo(MotorcycleModel::class);
    }
}
