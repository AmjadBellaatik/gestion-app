<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;

/**
 * Encapsulates all display-computation logic for the public verification portal.
 *
 * The Blade templates receive this presenter as $v and call methods to get
 * computed values — they contain no arithmetic, no match/switch, no metadata
 * extraction, and no client-resolution logic.
 *
 * Invariants:
 *  – $document is fully eager-loaded before this presenter is constructed.
 *  – All methods are read-only; nothing mutates $document.
 *  – No method exposes internal IDs, foreign keys, or audit metadata.
 */
final class DocumentVerificationPresenter
{
    private function __construct(private readonly Document $document) {}

    public static function from(Document $document): self
    {
        return new self($document);
    }

    // ── Client ────────────────────────────────────────────────────────────────

    public function clientType(): string
    {
        return $this->document->client?->client_type ?? 'person';
    }

    public function clientName(): string
    {
        $client = $this->document->client;
        if (! $client) {
            return '—';
        }

        return match ($client->client_type) {
            'company'        => $client->company_name ?? '—',
            'administration' => $client->administration_name ?? '—',
            default          => trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: '—',
        };
    }

    public function clientIce(): ?string
    {
        return $this->document->client?->ice;
    }

    public function clientCin(): ?string
    {
        return $this->document->client?->identity_number;
    }

    /** Localized label matching whichever identity_number clientCin() returns. */
    public function clientIdentityLabel(): string
    {
        return $this->document->client?->identity_label ?? __('messages.national_id');
    }

    public function displayClientIdentityLabel(): string
    {
        return $this->isQuote() ? __('messages.national_id') : $this->clientIdentityLabel();
    }

    public function clientPhone(): ?string
    {
        return $this->document->client?->phone;
    }

    public function clientAddress(): ?string
    {
        return $this->document->client?->address;
    }

    /** RC for companies, CIN/RC for persons, null for administrations. */
    public function clientIdentity(): ?string
    {
        $client = $this->document->client;
        if (! $client) {
            return null;
        }

        return match ($client->client_type) {
            'company'        => $client->rc,
            'administration' => null,
            default          => $client->rc ?: $client->identity_number,
        };
    }

    // ── Manual client (QUOTATION metadata) ───────────────────────────────────

    public function manualClientType(): string
    {
        return (string) data_get($this->document->metadata, 'manual_client_type', 'person');
    }

    public function manualClientName(): ?string
    {
        $type = $this->manualClientType();
        $meta = $this->document->metadata ?? [];

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

    public function manualClientPhone(): ?string
    {
        return data_get($this->document->metadata, 'manual_client_phone');
    }

    public function manualClientIce(): ?string
    {
        return data_get($this->document->metadata, 'manual_client_ice');
    }

    public function manualClientCin(): ?string
    {
        return data_get($this->document->metadata, 'manual_client_cin');
    }

    // ── Display client (QUOTE uses metadata; all others use the client relation)

    public function displayClientType(): string
    {
        return $this->isQuote() ? $this->manualClientType() : $this->clientType();
    }

    public function displayClientName(): string
    {
        return $this->isQuote()
            ? ($this->manualClientName() ?? '—')
            : $this->clientName();
    }

    public function displayClientPhone(): ?string
    {
        return $this->isQuote() ? $this->manualClientPhone() : $this->clientPhone();
    }

    public function displayClientIce(): ?string
    {
        return $this->isQuote() ? $this->manualClientIce() : $this->clientIce();
    }

    public function displayClientCin(): ?string
    {
        return $this->isQuote() ? $this->manualClientCin() : $this->clientCin();
    }

    // ── Counterparty (client OR supplier, used in the identity bar) ───────────

    public function counterpartyLabel(): string
    {
        return $this->isSupplierOrder()
            ? __('messages.provider')
            : __('messages.client');
    }

    public function counterpartyName(): ?string
    {
        if ($this->isSupplierOrder()) {
            $name = $this->supplierName();
            return $name ?: null;
        }

        $name = $this->displayClientName();
        return ($name !== '—') ? $name : null;
    }

    // ── Financial totals ──────────────────────────────────────────────────────

    public function totalTtc(): float
    {
        $total = (float) $this->document->total_amount;

        if ($total <= 0 && $this->document->items->isNotEmpty()) {
            $total = (float) $this->document->items->sum(fn (DocumentItem $i) => (float) $i->total);
        }

        return $total;
    }

    public function taxAmount(): float
    {
        $ttc = $this->totalTtc();

        return $ttc > 0
            ? round($ttc * (20 / 120), 2)
            : (float) $this->document->tax_amount;
    }

    public function subtotal(): float
    {
        $ttc = $this->totalTtc();

        return $ttc > 0
            ? round($ttc - $this->taxAmount(), 2)
            : (float) $this->document->subtotal;
    }

    // ── Document status badge ─────────────────────────────────────────────────

    /** Returns ['label' => string, 'color' => string] for the status-badge partial. */
    public function statusBadge(): array
    {
        return match ($this->document->status ?? 'generated') {
            'paid'      => ['label' => 'Payé',               'color' => 'emerald'],
            'partial'   => ['label' => 'Partiellement Payé', 'color' => 'blue'],
            'unpaid'    => ['label' => 'Non Payé',           'color' => 'amber'],
            'cancelled' => ['label' => 'Annulé',             'color' => 'red'],
            'archived'  => ['label' => 'Archivé',            'color' => 'purple'],
            'draft'     => ['label' => 'Brouillon',          'color' => 'slate'],
            'generated' => ['label' => 'Généré',             'color' => 'slate'],
            default     => ['label' => ucfirst((string) ($this->document->status ?? 'actif')), 'color' => 'slate'],
        };
    }

    // ── Motorcycle unit ───────────────────────────────────────────────────────

    public function primaryUnit()
    {
        return $this->document->items
                   ->first(fn (DocumentItem $i) => $i->motorcycleUnit)?->motorcycleUnit
               ?: $this->document->primaryMotorcycleUnit();
    }

    // ── Warranty ──────────────────────────────────────────────────────────────

    public function warrantyDurationLabel(): string
    {
        $value = data_get($this->document->metadata, 'warranty_duration_value')
            ?: data_get($this->document->metadata, 'warranty_years');
        $unit  = data_get($this->document->metadata, 'warranty_duration_unit', 'years');

        return trim($value . ' ' . __('messages.' . $unit));
    }

    public function warrantyKilometers(): ?string
    {
        $km = data_get($this->document->metadata, 'warranty_kilometers');
        return $km ? (string) $km : null;
    }

    // ── Supplier order ────────────────────────────────────────────────────────

    public function supplierName(): ?string
    {
        return data_get($this->document->metadata, 'manual_supplier_name')
            ?: $this->document->supplier?->name;
    }

    public function supplierPhone(): ?string
    {
        return data_get($this->document->metadata, 'manual_supplier_phone')
            ?: $this->document->supplier?->phone;
    }

    public function supplierEmail(): ?string
    {
        return data_get($this->document->metadata, 'manual_supplier_email')
            ?: $this->document->supplier?->email;
    }

    public function supplierAddress(): ?string
    {
        return data_get($this->document->metadata, 'manual_supplier_address')
            ?: $this->document->supplier?->address;
    }

    public function supplierIce(): ?string
    {
        return data_get($this->document->metadata, 'provider_ice')
            ?: $this->document->supplier?->ice;
    }

    public function supplierRc(): ?string
    {
        return data_get($this->document->metadata, 'provider_rc')
            ?: $this->document->supplier?->rc;
    }

    public function supplierQuoteReference(): ?string
    {
        return data_get($this->document->metadata, 'supplier_quote_number');
    }

    // ── Repair invoice ────────────────────────────────────────────────────────

    public function repairTicketNumber(): ?string
    {
        return $this->document->repairTicket?->ticket_number;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function isQuote(): bool
    {
        return $this->document->documentType?->code === DocumentType::QUOTATION;
    }

    private function isSupplierOrder(): bool
    {
        return $this->document->documentType?->code === DocumentType::SUPPLIER_ORDER;
    }
}
