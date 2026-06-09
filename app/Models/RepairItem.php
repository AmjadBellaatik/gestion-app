<?php

namespace App\Models;

use App\Services\Stock\StockService;
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

        // Deduct stock when a part/consumable is added to a repair ticket
        static::created(function (RepairItem $item) {
            if (! $item->product_id) {
                return;
            }
            if (! in_array($item->item_type, ['part', 'accessory', 'consumable'], true)) {
                return;
            }

            $ticket = $item->repairTicket;

            try {
                StockService::movement([
                    'company_id'         => $ticket?->company_id ?? session('company_id'),
                    'warehouse_id'       => $ticket?->warehouse_id ?? null,
                    'product_id'         => $item->product_id,
                    'motorcycle_unit_id' => $ticket?->motorcycle_unit_id ?? null,
                    'type'               => 'exit',
                    'movement_type'      => 'repair',
                    'quantity'           => (float) $item->quantity,
                    'unit_cost'          => (float) $item->unit_price,
                    'reference'          => $ticket?->ticket_number ?? ('RT-' . $item->repair_ticket_id),
                    'reference_type'     => RepairTicket::class,
                    'reference_id'       => $item->repair_ticket_id,
                    'notes'              => 'Repair ticket ' . ($ticket?->ticket_number ?? $item->repair_ticket_id),
                    'user_id'            => auth()->user()?->getAuthIdentifier(),
                ]);
            } catch (\Throwable) {
                // Stock failures must not block item creation
            }
        });

        // Restore stock when a part/consumable is removed from a repair ticket
        static::deleted(function (RepairItem $item) {
            if (! $item->product_id) {
                return;
            }
            if (! in_array($item->item_type, ['part', 'accessory', 'consumable'], true)) {
                return;
            }

            $ticket = $item->repairTicket;

            try {
                StockService::movement([
                    'company_id'         => $ticket?->company_id ?? session('company_id'),
                    'warehouse_id'       => $ticket?->warehouse_id ?? null,
                    'product_id'         => $item->product_id,
                    'motorcycle_unit_id' => $ticket?->motorcycle_unit_id ?? null,
                    'type'               => 'entry',
                    'movement_type'      => 'repair_return',
                    'quantity'           => (float) $item->quantity,
                    'reference'          => $ticket?->ticket_number ?? ('RT-' . $item->repair_ticket_id),
                    'reference_type'     => RepairTicket::class,
                    'reference_id'       => $item->repair_ticket_id,
                    'notes'              => 'Stock restored: item removed from repair ' . ($ticket?->ticket_number ?? ''),
                    'user_id'            => auth()->user()?->getAuthIdentifier(),
                ]);
            } catch (\Throwable) {
                //
            }
        });
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
