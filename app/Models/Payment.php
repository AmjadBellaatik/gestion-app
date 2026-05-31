<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

class Payment extends Model
{
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
                // Immediately credit the sale/repair balance.
                $service->applyPayment($model);
            } else {
                // Pending payments (cheque / transfer) put linked motorcycle units on hold.
                $service->holdLinkedMotorcycleUnits($model);
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

            if (\in_array($model->status, ['rejected', 'cancelled', 'canceled'], true)) {
                // Payment was cancelled — release holds and reverse if needed.
                $service->reversePayment($model);
            }

            // Cheque bounced — block client / reseller.
            if ($model->payment_method === 'cheque' && $model->status === 'bounced') {
                $service->handleBouncedCheque($model);
            }
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
}
