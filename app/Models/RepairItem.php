<?php

namespace App\Models;

use App\Models\StockMovement;
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
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
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

        static::created(function (RepairItem $item) {
            if (! $item->product_id) {
                return;
            }

            if (! \in_array($item->item_type, ['part', 'accessory', 'consumable'], true)) {
                return;
            }

            $ticket = $item->repairTicket;

            StockMovement::create([
                'company_id'    => session('company_id'),
                'product_id'    => $item->product_id,
                'movement_type' => 'repair',
                'type'          => 'exit',
                'quantity'      => (float) $item->quantity,
                'unit_cost'     => (float) $item->unit_price,
                'reference'     => $ticket?->ticket_number ?? ('RT-' . $item->repair_ticket_id),
                'reference_type' => RepairTicket::class,
                'reference_id'  => $item->repair_ticket_id,
                'notes'         => 'Repair ticket ' . ($ticket?->ticket_number ?? $item->repair_ticket_id),
                'user_id'       => auth()->id(),
            ]);
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
