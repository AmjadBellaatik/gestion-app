<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\RepairTicket;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\Warranty;
use App\Models\Scopes\CompanyScope;
use App\Notifications\SaleCreatedNotification;
use App\Services\Payments\PaymentService;
use App\Services\Sales\SaleService;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'company_id',
        'client_id',
        'reseller_id',
        'repair_ticket_id',
        'user_id',
        'sale_number',
        'sale_date',
        'sale_type',
        'subtotal',
        'discount',
        'discount_note',
        'tax',
        'total',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'status',
        'returned_at',
        'notes',
        'purchase_order_number',

    ];

    protected $casts = [
        'returned_at' => 'datetime',
        'sale_date'   => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (session()->has('company_id')) {

                $model->company_id =
                    session('company_id');

            }

            if (auth()->check()) {

                $model->user_id =
                    auth()->id();

            }

            // Effective sale date defaults to today; never null.
            if (empty($model->sale_date)) {
                $model->sale_date = now()->toDateString();
            }

        });

        // AUDIT TRAIL: log every sale_date modification, immutably.
        static::updating(function (Sale $model) {
            if ($model->isDirty('sale_date')) {
                \App\Models\SaleDateLog::create([
                    'company_id' => $model->company_id,
                    'sale_id'    => $model->id,
                    'user_id'    => auth()->id(),
                    'user_name'  => auth()->user()?->name,
                    'old_date'   => $model->getOriginal('sale_date'),
                    'new_date'   => $model->sale_date,
                    'changed_at' => now(),
                ]);
            }
        });

        static::created(function (Sale $model) {
            $admins = User::role(['Admin', 'Super Admin'])->where('status', true)->get();
            $notification = new SaleCreatedNotification($model);

            $admins->each(function (User $admin) use ($notification) {
                try {
                    $admin->notify($notification);
                } catch (\Throwable) {
                    //
                }
            });
        });

        static::saved(function (Sale $model) {
            if ($model->reseller_id) {
                $model->reseller?->recalculate();
            }
        });

        static::deleting(function (Sale $model) {
            SaleService::cleanupRelatedRecordsForDeletion($model);
        });

        static::deleted(function (Sale $model) {
            if ($model->reseller_id) {
                $model->reseller?->recalculate();
            }
        });

        // NOTE: Auto-payment creation removed.
        // Payment status is managed exclusively by PaymentService::applyPayment()
        // which is called from the Payment model observer. Creating payments
        // automatically from Sale::updated() caused zero-amount and duplicate
        // payments when payment_status changed before a Payment record existed.
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function saleDateLogs()
    {
        return $this->hasMany(SaleDateLog::class)->latest('changed_at');
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function client()
    {
        return $this->belongsTo(
            Client::class
        );
    }

    public function reseller()
    {
        return $this->belongsTo(
            Reseller::class
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function items()
    {
        return $this->hasMany(
            SaleItem::class
        );
    }

    public function saleItems()
    {
        return $this->hasMany(
            SaleItem::class
        );
    }

    public function payments()
    {
        return $this->hasMany(
            Payment::class
        );
    }

    public function documents()
    {
        return $this->hasMany(
            Document::class
        );
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    public function repairTicket()
    {
        return $this->belongsTo(RepairTicket::class);
    }
}
