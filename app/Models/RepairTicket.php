<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Scopes\CompanyScope;
use App\Notifications\RepairStatusNotification;
use App\Services\Payments\PaymentService;
use App\Services\Workshop\RepairService;
use App\Services\Workshop\RepairStockService;
use Illuminate\Support\Facades\DB;

class RepairTicket extends Model
{
    use SoftDeletes;

    /**
     * Guard flag: while a ticket-level stock reversal is running (delete /
     * cancel / restore), the per-item RepairItem observers must NOT fire their
     * own stock movements — otherwise a cascade would double-restore. Ticket
     * operations restore in bulk using the net-consumed quantity.
     */
    public static bool $reversingStock = false;

    public const REPAIR_TYPES = [
        'warranty',
        'paid',
        'internal',
    ];

    public const STATUSES = [
        'open',
        'diagnostic',
        'waiting_approval',
        'approved',
        'waiting_parts',
        'in_progress',
        'completed',
        'delivered',
        'closed',
        'cancelled',
    ];

    public const PRIORITIES = [
        'low',
        'normal',
        'high',
        'urgent',
    ];

    protected $fillable = [
        'company_id',
        'client_id',
        'sale_id',
        'motorcycle_id',
        'motorcycle_unit_id',
        'technician_id',
        'ticket_number',
        'repair_type',
        'repair_type_id',
        'status',
        'priority',
        'problem_description',
        'diagnostic',
        'diagnosis',
        'technician_notes',
        'before_state',
        'after_state',
        'warranty_status',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'discount_amount',
        'discount_validated',
        'discount_validated_by',
        'discount_validated_at',
        'discount_note',
        'report_path',
        'invoice_document_id',
        'is_warranty',
        'created_by',
        'mileage',
        'is_foreign_vehicle',
        'foreign_brand',
        'foreign_model',
        'foreign_chassis',
        'foreign_year',
        'foreign_color',
        'payment_status',
        'paid_amount',
        'remaining_amount',
        'opened_at',
        'started_at',
        'diagnostic_at',
        'assigned_at',
        'finished_at',
        'completed_at',
        'delivered_at',
        'closed_at',
        'paid_at',
        'cancelled_at',
    ];

    protected $casts = [
        'is_warranty'           => 'boolean',
        'is_foreign_vehicle'    => 'boolean',
        'discount_validated'    => 'boolean',
        'labor_cost'            => 'decimal:2',
        'parts_cost'            => 'decimal:2',
        'total_cost'            => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'paid_amount'           => 'decimal:2',
        'remaining_amount'      => 'decimal:2',
        'opened_at'             => 'datetime',
        'started_at'            => 'datetime',
        'diagnostic_at'         => 'datetime',
        'assigned_at'           => 'datetime',
        'finished_at'           => 'datetime',
        'completed_at'          => 'datetime',
        'delivered_at'          => 'datetime',
        'closed_at'             => 'datetime',
        'paid_at'               => 'datetime',
        'cancelled_at'          => 'datetime',
        'discount_validated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (RepairTicket $model) {
            if (session()->has('company_id')) {
                $model->company_id = session('company_id');
            }

            $model->created_by    ??= auth()->id();
            $model->status        ??= 'open';
            $model->priority      ??= 'normal';
            $model->payment_status ??= 'unpaid';
            $model->opened_at     ??= now();

            if (empty($model->ticket_number)) {
                $model->ticket_number = self::generateTicketNumber();
            }

            if ($model->motorcycle_unit_id && ! $model->is_foreign_vehicle) {
                MotorcycleUnit::withoutGlobalScopes()
                    ->where('id', $model->motorcycle_unit_id)
                    ->update(['status' => 'in_repair']);
            }
        });

        /*
        |----------------------------------------------------------------------
        | Internal repairs are work on company-owned inventory: they never
        | generate customer-visible revenue, so labour is always 0 MAD. This
        | is the authoritative safety net — enforced regardless of how the
        | ticket is created/updated (form, import, console).
        |----------------------------------------------------------------------
        */
        static::saving(function (RepairTicket $model) {
            if ($model->repair_type === 'internal') {
                $model->labor_cost = 0;
            }

            // Warranty flag is derived from the chosen type — never set manually.
            $model->is_warranty = $model->repair_type === 'warranty';
        });

        static::updating(function (RepairTicket $model) {
            if (! $model->isDirty('status') || ! $model->motorcycle_unit_id || $model->is_foreign_vehicle) {
                return;
            }

            if (in_array($model->status, ['completed', 'delivered', 'closed'])) {
                $unit = MotorcycleUnit::withoutGlobalScopes()->find($model->motorcycle_unit_id);
                if ($unit) {
                    $unit->update(['status' => $unit->client_id ? 'sold' : 'available']);
                }
            } elseif ($model->status === 'cancelled') {
                MotorcycleUnit::withoutGlobalScopes()
                    ->where('id', $model->motorcycle_unit_id)
                    ->update(['status' => 'available']);

                // Restore stock for all items still on the ticket.
                // This is the sole stock-restoration path for cancellation —
                // RepairWorkflowService no longer calls RepairService::restoreStockForCancellation().
                self::restoreItemsStock($model);
            }
        });

        static::updated(function (RepairTicket $model) {
            // Auto-create payment when repair is marked paid (for non-warranty, non-internal)
            if (
                $model->wasChanged('payment_status')
                && $model->payment_status === 'paid'
                && ! in_array($model->repair_type, ['warranty', 'internal'])
                && ! Payment::where('repair_ticket_id', $model->id)->exists()
            ) {
                try {
                    PaymentService::createFromRepair($model, 'cash');
                } catch (\Throwable) {
                    //
                }
            }

            if (! $model->wasChanged('status')) {
                return;
            }

            $oldStatus = $model->getOriginal('status');
            $newStatus = $model->status;

            $usersToNotify = collect();
            if ($model->creator && $model->creator->status) {
                $usersToNotify->push($model->creator);
            }

            $model->assignedTechnicians->each(function ($assignment) use (&$usersToNotify) {
                $user = User::find($assignment->technician?->user_id ?? null);
                if ($user && $user->status && ! $usersToNotify->contains('id', $user->id)) {
                    $usersToNotify->push($user);
                }
            });

            $notification = new RepairStatusNotification($model, $oldStatus, $newStatus);
            $usersToNotify->each(function (User $user) use ($notification) {
                try {
                    $user->notify($notification);
                } catch (\Throwable) {
                    //
                }
            });
        });

        /*
        |----------------------------------------------------------------------
        | DELETE — return all consumed stock + reverse financials.
        | Stock is restored in bulk (net-based) and the per-item observers are
        | suppressed via the guard so a force-delete cascade cannot double it.
        | Financial reversal mirrors the sale flow: soft-deleting each repair
        | Payment triggers PaymentService::reversePayment (ledger + treasury +
        | balance), and the repair JournalEntry is removed.
        |----------------------------------------------------------------------
        */
        static::deleted(function (RepairTicket $model) {
            self::$reversingStock = true;

            try {
                RepairStockService::restoreTicket(
                    $model,
                    'Stock restored: repair ' . ($model->ticket_number ?? $model->getKey()) . ' deleted'
                );

                RepairService::reverseTicketFinancials($model);
            } catch (\Throwable) {
                //
            } finally {
                self::$reversingStock = false;
            }
        });

        /*
        |----------------------------------------------------------------------
        | RESTORE — re-deduct the previously consumed quantities (net-based).
        |----------------------------------------------------------------------
        */
        static::restored(function (RepairTicket $model) {
            self::$reversingStock = true;

            try {
                RepairStockService::reconsumeTicket($model);
            } catch (\Throwable) {
                //
            } finally {
                self::$reversingStock = false;
            }
        });
    }

    /**
     * Restore all stock still consumed by the ticket (net-based, idempotent).
     * Routed through RepairStockService so every movement resolves a valid
     * warehouse — the old inline call passed a null warehouse_id and threw.
     */
    private static function restoreItemsStock(RepairTicket $model): void
    {
        self::$reversingStock = true;

        try {
            RepairStockService::restoreTicket(
                $model,
                'Stock restored: repair ' . ($model->ticket_number ?? $model->getKey()) . ' cancelled'
            );
        } catch (\Throwable) {
            //
        } finally {
            self::$reversingStock = false;
        }
    }

    public static function generateTicketNumber(): string
    {
        $year      = now()->format('Y');
        $companyId = (int) (session('company_id') ?? 0);

        DB::select('SELECT GET_LOCK(?, 10) AS locked', ["rep_seq_{$companyId}_{$year}"]);

        try {
            $last = self::withoutGlobalScopes()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereYear('created_at', $year)
                ->orderByDesc('id')
                ->value('ticket_number');

            $next = 1;
            if ($last && preg_match('/\-(\d+)$/', $last, $m)) {
                $next = (int) $m[1] + 1;
            }

            return 'REP-' . $year . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        } finally {
            DB::select('SELECT RELEASE_LOCK(?)', ["rep_seq_{$companyId}_{$year}"]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'motorcycle_id');
    }

    public function motorcycleUnit(): BelongsTo
    {
        return $this->belongsTo(MotorcycleUnit::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function repairType(): BelongsTo
    {
        return $this->belongsTo(RepairType::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RepairItem::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(RepairItem::class)->where('item_type', 'part');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(RepairItem::class)->where('item_type', 'consumable');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RepairStep::class)->orderBy('sort_order');
    }

    public function assignedTechnicians(): HasMany
    {
        return $this->hasMany(RepairTicketTechnician::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function discountValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discount_validated_by');
    }

    public function invoiceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'invoice_document_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function recalculateCosts(): void
    {
        $this->parts_cost = $this->items()->sum('total');
        $this->total_cost = round(
            (float) $this->labor_cost + (float) $this->parts_cost - (float) $this->discount_amount,
            2
        );
        $this->saveQuietly();
    }

    public function getVehicleDisplayAttribute(): string
    {
        if ($this->is_foreign_vehicle) {
            return implode(' ', array_filter([
                $this->foreign_brand,
                $this->foreign_model,
                $this->foreign_chassis ? "({$this->foreign_chassis})" : null,
            ]));
        }

        return $this->motorcycleUnit?->chassis_number ?? '-';
    }
}
