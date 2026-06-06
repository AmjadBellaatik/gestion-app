<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Services\Documents\DocumentNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use SoftDeletes;

    public const TAX_RATE = 20.00;

    protected $fillable = [
        'company_id',
        'document_type_id',
        'document_template_id',
        'template_version',
        'client_id',
        'supplier_id',
        'reseller_id',
        'sale_id',
        'repair_ticket_id',
        'uuid',
        'verification_url',
        'document_number',
        'document_date',
        'language',
        'status',
        'subtotal',
        'tax_rate',
        'tax',
        'tax_amount',
        'discount_amount',
        'total',
        'total_amount',
        'notes',
        'pdf_path',
        'generated_at',
        'generated_by',
        'metadata',
    ];

    protected $casts = [
        'document_date' => 'date',
        'generated_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Document $document) {
            $document->company_id ??= session('company_id');
            $document->uuid ??= (string) Str::uuid();
            $document->document_date ??= now()->toDateString();
            $document->language = 'fr';
            $document->status ??= 'generated';
            $document->tax_rate ??= self::TAX_RATE;

            if (empty($document->document_number) && $document->document_type_id) {
                $document->document_number = DocumentNumberService::generate($document);
            }

            $document->verification_url = url('/verify/document/' . $document->uuid);
        });

        static::saving(function (Document $document) {
            $document->tax_rate = self::TAX_RATE;
            // subtotal is the post-discount HT amount (set by DocumentService::recalculateTotals)
            // do not subtract discount_amount again — it is already factored into subtotal
            $document->tax_amount = round((float) $document->subtotal * 0.20, 2);
            $document->total_amount = round((float) $document->subtotal + (float) $document->tax_amount, 2);
            $document->tax = $document->tax_amount;
            $document->total = $document->total_amount;

            if ($document->uuid && empty($document->verification_url)) {
                $document->verification_url = url('/verify/document/' . $document->uuid);
            }
        });

        static::deleting(function (Document $document) {
            // On soft delete: mangle the document_number so the DB unique
            // constraint (company_id, document_number) does not block a future
            // document from reusing the freed number.
            //
            // The original number is preserved in metadata for audit purposes.
            // Hard deletes (forceDelete) remove the row entirely, so no mangling
            // is needed — the number is freed by the row's disappearance.
            if ($document->isForceDeleting()) {
                return;
            }

            $original = (string) ($document->document_number ?? '');

            if ($original === '' || str_contains($original, '__VOID_')) {
                return; // Already voided or no number assigned — nothing to do.
            }

            $void = $original . '__VOID_' . now()->format('YmdHis') . '_' . $document->id;

            $document->metadata = array_merge(
                (array) ($document->metadata ?? []),
                ['original_document_number' => $original]
            );
            $document->document_number = $void;

            // saveQuietly suppresses all model events so this update does not
            // re-trigger creating/saving/deleting cycles.
            $document->saveQuietly();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function repairTicket(): BelongsTo
    {
        return $this->belongsTo(RepairTicket::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('line_sort');
    }

    public function generatedPdfs(): HasMany
    {
        return $this->hasMany(GeneratedPdf::class)->latest('generated_at');
    }

    public function primaryMotorcycleUnit(): ?MotorcycleUnit
    {
        $item = $this->relationLoaded('items')
            ? $this->items->first(fn (DocumentItem $item) => $item->motorcycle_unit_id || $item->motorcycleUnit)
            : null;

        if (! $item) {
            $item = $this->items()
                ->whereNotNull('motorcycle_unit_id')
                ->with('motorcycleUnit.motorcycleModel.homologation')
                ->first();
        } elseif (! $item->relationLoaded('motorcycleUnit')) {
            $item->load('motorcycleUnit.motorcycleModel.homologation');
        }

        return $item?->motorcycleUnit;
    }
}
