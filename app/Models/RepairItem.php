<?php

namespace App\Models;

use App\Services\Workshop\RepairStockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairItem extends Model
{
    protected $fillable = [
        'repair_ticket_id',
        'product_id',
        'item_type',
        'quantity',
        'unit_price',
        'total',
        'item_description',
        'discount_amount',
    ];

    protected $casts = [
        'quantity'        => 'decimal:2',
        'unit_price'      => 'decimal:2',
        'total'           => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (RepairItem $item) {
            $item->total = round(
                ((float) $item->quantity * (float) $item->unit_price) - (float) $item->discount_amount,
                2
            );
        });

        /*
        |----------------------------------------------------------------------
        | ADD — consume stock (OUT) + recalc ticket totals.
        | All stock changes flow through RepairStockService so they always
        | produce a StockMovement with a resolved warehouse (the previous code
        | passed a null warehouse, so every movement threw and was swallowed).
        |----------------------------------------------------------------------
        */
        static::created(function (RepairItem $item) {
            if (RepairTicket::$reversingStock) {
                return;
            }

            $ticket = $item->repairTicket;
            if (! $ticket) {
                return;
            }

            try {
                RepairStockService::consume($ticket, $item);
            } catch (\Throwable) {
                // Stock failures must never block item creation
            }

            self::syncTicket($ticket);
        });

        /*
        |----------------------------------------------------------------------
        | EDIT — only the quantity delta leaves/returns stock (never double).
        | Old 2 → New 5 ⇒ 3 OUT.  Old 5 → New 2 ⇒ 3 IN.
        |----------------------------------------------------------------------
        */
        static::updated(function (RepairItem $item) {
            if (RepairTicket::$reversingStock) {
                return;
            }

            $ticket = $item->repairTicket;
            if (! $ticket) {
                return;
            }

            if ($item->wasChanged('quantity')) {
                $oldQty = (float) $item->getOriginal('quantity');
                $newQty = (float) $item->quantity;

                try {
                    RepairStockService::applyDelta($ticket, $item, $oldQty, $newQty);
                } catch (\Throwable) {
                    //
                }
            }

            self::syncTicket($ticket);
        });

        /*
        |----------------------------------------------------------------------
        | REMOVE — restore stock (IN) + recalc ticket totals.
        |----------------------------------------------------------------------
        */
        static::deleted(function (RepairItem $item) {
            if (RepairTicket::$reversingStock) {
                // Ticket-level delete/cancel handles restoration in bulk.
                return;
            }

            $ticket = $item->repairTicket;
            if (! $ticket) {
                return;
            }

            try {
                RepairStockService::restoreItem($ticket, $item);
            } catch (\Throwable) {
                //
            }

            self::syncTicket($ticket);
        });
    }

    /**
     * Keep the ticket's parts_cost / total_cost in step with its items, and
     * resync accounting when the ticket has already been finalised.
     */
    protected static function syncTicket(RepairTicket $ticket): void
    {
        try {
            $ticket->recalculateCosts();
            \App\Services\Workshop\RepairService::syncAccountingIfFinalized($ticket);
        } catch (\Throwable) {
            //
        }
    }

    public function repairTicket(): BelongsTo
    {
        return $this->belongsTo(RepairTicket::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
