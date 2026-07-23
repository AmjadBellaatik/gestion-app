<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

class Payment extends Model
{
    use \App\Models\Concerns\Auditable;

    use SoftDeletes;

    public const METHODS = [
        'cash',
        'card',
        'cheque',
        'bank_transfer',
    ];

    public const CHEQUE_STATUSES = [
        'received',
        'paid',
        'bounced',
    ];

    public const TRANSFER_STATUSES = [
        'sent',
        'received',
    ];

    protected $fillable = [
        'company_id',
        'sale_id',
        'repair_ticket_id',
        'client_id',
        'document_id',
        'amount',
        'payment_method',
        'reference',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Payment $model) {
            if (Session::has('company_id')) {
                $model->company_id = Session::get('company_id');
            }

            // Only auto-assign status when it hasn't been explicitly set by the caller.
            if (blank($model->status)) {
                $model->status = match ($model->payment_method) {
                    'cash', 'card' => 'paid',
                    'cheque'       => 'pending_validation',
                    default        => 'pending',
                };
            }
        });

        static::created(function (Payment $model) {
            $service = app(PaymentService::class);

            if ($model->status === 'paid') {
                // Cash / card — immediately credit balance + create ledger entries.
                $service->applyPayment($model);
            } else {
                // Cheque / bank_transfer — put units on hold AND immediately
                // reflect the committed amount in the sale/repair balance.
                $service->holdLinkedMotorcycleUnits($model);
                $service->reflectPendingPayment($model);
            }
        });

        static::updated(function (Payment $model) {
            if (! $model->wasChanged('status')) {
                return;
            }

            $service = app(PaymentService::class);

            if ($model->status === 'paid') {
                // Payment was validated — credit balance and finalise unit status.
                $service->applyPayment($model);
            }

            // Bank transfer confirmed by bank — treat as paid.
            if ($model->payment_method === 'bank_transfer' && $model->status === 'received') {
                $service->applyPayment($model);
            }

            if (\in_array($model->status, ['rejected', 'cancelled', 'canceled'], true)) {
                // Payment was cancelled — release holds and reverse if needed.
                $service->reversePayment($model);
            }

            // Cheque bounced — block client / reseller.
            if ($model->payment_method === 'cheque' && $model->status === 'bounced') {
                $service->handleBouncedCheque($model);
            }
        });

        // Reverse balance BEFORE the soft-delete so the authoritative SUM is still correct.
        static::deleting(function (Payment $model) {
            $terminal = ['rejected', 'cancelled', 'canceled', 'bounced'];
            if (! \in_array($model->status, $terminal, true)) {
                app(PaymentService::class)->reversePayment($model);
            }
        });

        static::saved(function (Payment $model) {
            $reseller = $model->sale?->reseller;
            $reseller?->recalculate();
        });

        static::deleted(function (Payment $model) {
            $reseller = $model->sale?->reseller;
            $reseller?->recalculate();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function repairTicket()
    {
        return $this->belongsTo(RepairTicket::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function chequePayment()
    {
        return $this->hasOne(ChequePayment::class);
    }

    public function bankTransferPayment()
    {
        return $this->hasOne(BankTransferPayment::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Display helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Counterparty name for list/table display — a reseller-linked sale's
     * reseller name takes priority (payments for reseller sales have no
     * client_id), falling back to the payment's own client. Mirrors
     * Document::partyDisplayName() so the same reseller-vs-client resolution
     * is used consistently across the app.
     */
    public function partyDisplayName(): ?string
    {
        $reseller = $this->sale?->reseller?->name
            ?? ($this->sale?->reseller_id
                ? $this->sale->reseller()->withoutGlobalScopes()->value('name')
                : null);

        if (filled($reseller)) {
            return $reseller;
        }

        return $this->client?->display_name;
    }
}
