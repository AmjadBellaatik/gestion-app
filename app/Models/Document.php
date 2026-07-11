<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Services\Documents\DocumentNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $company_id
 * @property int $document_type_id
 * @property int|null $document_template_id
 * @property int|null $client_id
 * @property int|null $supplier_id
 * @property int|null $reseller_id
 * @property int|null $sale_id
 * @property int|null $repair_ticket_id
 * @property string|null $invoice_source
 * @property string $uuid
 * @property string $verification_url
 * @property string $document_number
 * @property int|null $document_year
 * @property int|null $sequence_number
 * @property \Carbon\Carbon $document_date
 * @property string $language
 * @property string $status
 * @property float $subtotal
 * @property float $tax_rate
 * @property float $tax
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total
 * @property float $total_amount
 * @property string|null $notes
 * @property string|null $pdf_path
 * @property \Carbon\Carbon|null $generated_at
 * @property int|null $generated_by
 * @property array|null $metadata
 */
class Document extends Model
{
    use \App\Models\Concerns\Auditable;

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
        'invoice_source',
        'uuid',
        'verification_url',
        'document_number',
        'document_year',
        'sequence_number',
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
            $document->language ??= 'fr';
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

    /**
     * Human-readable name of the document's counterparty, for list/detail
     * display across every document type. Resolves, in order:
     *   1. a reseller — linked directly on the document or through its sale
     *      (reseller sales set reseller_id and null out client_id);
     *   2. a registered client (client_id);
     *   3. a manual client captured in quotation metadata (no client_id).
     * Read-only — does not affect document generation.
     */
    public function partyDisplayName(): ?string
    {
        $reseller = $this->reseller?->name
            ?? $this->sale?->reseller?->name
            ?? ($this->reseller_id ? $this->reseller()->withoutGlobalScopes()->value('name') : null)
            ?? ($this->sale?->reseller_id ? $this->sale->reseller()->withoutGlobalScopes()->value('name') : null);

        if (filled($reseller)) {
            return $reseller;
        }

        if (filled($this->client?->display_name)) {
            return $this->client->display_name;
        }

        return $this->manualClientName();
    }

    /**
     * Name of the manual client captured in quotation metadata (documents with
     * no client_id — e.g. Devis). Mirrors DocumentVerificationPresenter so the
     * list, detail view and PDF stay consistent.
     */
    public function manualClientName(): ?string
    {
        $meta = $this->metadata ?? [];
        $type = (string) data_get($meta, 'manual_client_type', 'person');

        $name = match ($type) {
            'company'        => data_get($meta, 'manual_client_company_name'),
            'administration' => data_get($meta, 'manual_client_administration_name'),
            default          => trim(
                data_get($meta, 'manual_client_first_name', '') . ' ' .
                data_get($meta, 'manual_client_last_name', '')
            ),
        };

        return ($name && $name !== ' ') ? $name : data_get($meta, 'manual_client_name');
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
