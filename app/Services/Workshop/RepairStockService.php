<?php

namespace App\Services\Workshop;

use App\Models\RepairItem;
use App\Models\RepairTicket;
use App\Models\StockMovement;
use App\Services\Stock\StockService;

/**
 * Central stock-synchronisation logic for repair tickets.
 *
 * Every repair stock change flows through here so it ALWAYS goes through a
 * StockMovement record (never a direct product mutation), mirrors the sale
 * architecture, and resolves a valid warehouse — the missing piece that made
 * every previous repair StockService::movement() call throw and be swallowed.
 *
 * Stock math is movement-driven (Product::getCurrentStockAttribute):
 *   - type = exit  → counts as OUT (consumed)
 *   - type = entry → counts as IN  (restored)
 *
 * Restores are computed from the *net still-consumed* quantity per product on
 * the ticket, so a cancel-then-delete (or any double trigger) can never
 * over-restore: once net reaches zero, no further movement is written.
 */
class RepairStockService
{
    /** item_type values that represent physical stock. */
    public const STOCK_ITEM_TYPES = ['part', 'accessory', 'consumable'];

    /**
     * movement_type values, constrained by the stock_movements ENUM
     * (sale, purchase, transfer, repair, return, adjustment).
     *   - OUT (consume) → 'repair'
     *   - IN  (restore) → 'return'  (counted as IN by Product::getCurrentStock)
     * The specific reason (return / cancel / delete) is recorded in the notes.
     */
    public const OUT_MOVEMENT_TYPE = 'repair';
    public const IN_MOVEMENT_TYPE  = 'return';

    public static function isStockItem(RepairItem $item): bool
    {
        return $item->product_id
            && in_array($item->item_type, self::STOCK_ITEM_TYPES, true);
    }

    /**
     * Resolve the warehouse for a product's repair movement from its most recent
     * entry movement — identical strategy to SaleService::resolveProductWarehouse().
     */
    public static function resolveWarehouse(int $productId): ?int
    {
        if ($productId <= 0) {
            return null;
        }

        return StockMovement::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->whereIn('type', ['entry', 'in'])
            ->whereIn('movement_type', ['purchase', 'return', 'adjustment', 'transfer'])
            ->whereNotNull('warehouse_id')
            ->orderByDesc('id')
            ->value('warehouse_id');
    }

    /**
     * Net quantity of a product still consumed by this ticket
     * (sum of OUT movements − sum of IN movements). Zero once fully restored.
     */
    public static function netConsumed(int $ticketId, int $productId): float
    {
        $out = (float) StockMovement::withoutGlobalScopes()
            ->where('reference_type', RepairTicket::class)
            ->where('reference_id', $ticketId)
            ->where('product_id', $productId)
            ->where('type', 'exit')
            ->sum('quantity');

        $in = (float) StockMovement::withoutGlobalScopes()
            ->where('reference_type', RepairTicket::class)
            ->where('reference_id', $ticketId)
            ->where('product_id', $productId)
            ->where('type', 'entry')
            ->sum('quantity');

        return round($out - $in, 2);
    }

    /**
     * Write a single repair stock movement. Returns null (no-op) when the item
     * is not a stock item, the quantity is non-positive, or no warehouse can be
     * resolved — never throws, so it can run safely inside model observers.
     */
    public static function move(
        RepairTicket $ticket,
        int $productId,
        string $type,            // 'exit' (OUT) | 'entry' (IN)
        string $movementType,    // 'repair' | 'repair_return' | 'repair_cancel' | 'repair_delete'
        float $quantity,
        ?float $unitCost = null,
        ?string $notes = null
    ): ?StockMovement {
        if ($productId <= 0 || $quantity <= 0) {
            return null;
        }

        $warehouseId = self::resolveWarehouse($productId);
        if (! $warehouseId) {
            return null;
        }

        return StockService::movement([
            'company_id'         => $ticket->company_id,
            'warehouse_id'       => $warehouseId,
            'product_id'         => $productId,
            'motorcycle_unit_id' => $ticket->motorcycle_unit_id ?? null,
            'type'               => $type,
            'movement_type'      => $movementType,
            'quantity'           => round($quantity, 2),
            // unit_cost is NOT NULL at the DB level; default to 0 for restores
            // where no price applies.
            'unit_cost'          => $unitCost ?? 0.0,
            'reference'          => $ticket->ticket_number ?? ('RT-' . $ticket->getKey()),
            'reference_type'     => RepairTicket::class,
            'reference_id'       => $ticket->getKey(),
            'notes'              => $notes ?? ('Repair ' . ($ticket->ticket_number ?? $ticket->getKey())),
            'user_id'            => auth()->user()?->getAuthIdentifier(),
        ]);
    }

    /* ── Per-item operations (driven by RepairItem observers) ──────────────── */

    public static function consume(RepairTicket $ticket, RepairItem $item): void
    {
        if (! self::isStockItem($item)) {
            return;
        }

        self::move(
            $ticket,
            (int) $item->product_id,
            'exit',
            'repair',
            (float) $item->quantity,
            (float) $item->unit_price,
            'Consumed by repair ' . ($ticket->ticket_number ?? $ticket->getKey())
        );
    }

    /**
     * Apply only the delta when a repair item quantity changes.
     * Increase → OUT delta; decrease → IN delta. Never double-consumes.
     */
    public static function applyDelta(RepairTicket $ticket, RepairItem $item, float $oldQty, float $newQty): void
    {
        if (! self::isStockItem($item)) {
            return;
        }

        $delta = round($newQty - $oldQty, 2);
        if ($delta === 0.0) {
            return;
        }

        if ($delta > 0) {
            self::move(
                $ticket,
                (int) $item->product_id,
                'exit',
                'repair',
                $delta,
                (float) $item->unit_price,
                'Repair ' . ($ticket->ticket_number ?? $ticket->getKey()) . ' quantity increased'
            );
        } else {
            self::move(
                $ticket,
                (int) $item->product_id,
                'entry',
                self::IN_MOVEMENT_TYPE,
                abs($delta),
                null,
                'Repair ' . ($ticket->ticket_number ?? $ticket->getKey()) . ' quantity decreased'
            );
        }
    }

    public static function restoreItem(RepairTicket $ticket, RepairItem $item): void
    {
        if (! self::isStockItem($item)) {
            return;
        }

        self::move(
            $ticket,
            (int) $item->product_id,
            'entry',
            self::IN_MOVEMENT_TYPE,
            (float) $item->quantity,
            null,
            'Stock restored: item removed from repair ' . ($ticket->ticket_number ?? $ticket->getKey())
        );
    }

    /* ── Ticket-level operations (delete / cancel / restore) ───────────────── */

    /**
     * Restore ALL stock still consumed by the ticket — net-based and idempotent.
     * Used on ticket cancellation and deletion. A second call is a no-op.
     */
    public static function restoreTicket(RepairTicket $ticket, string $reason): void
    {
        $ticket->loadMissing('items');

        $productIds = $ticket->items
            ->filter(fn (RepairItem $i) => self::isStockItem($i))
            ->pluck('product_id')
            ->unique()
            ->values();

        foreach ($productIds as $productId) {
            $net = self::netConsumed($ticket->getKey(), (int) $productId);
            if ($net <= 0) {
                continue;
            }

            self::move($ticket, (int) $productId, 'entry', self::IN_MOVEMENT_TYPE, $net, null, $reason);
        }
    }

    /**
     * Re-consume the stock for a restored ticket — net-based and idempotent.
     * For each product the target consumption is the sum of its item quantities;
     * only the missing difference is taken back out of stock.
     */
    public static function reconsumeTicket(RepairTicket $ticket): void
    {
        $ticket->loadMissing('items');

        $byProduct = $ticket->items
            ->filter(fn (RepairItem $i) => self::isStockItem($i))
            ->groupBy('product_id');

        foreach ($byProduct as $productId => $items) {
            $target = round((float) $items->sum(fn (RepairItem $i) => (float) $i->quantity), 2);
            $net    = self::netConsumed($ticket->getKey(), (int) $productId);
            $delta  = round($target - $net, 2);

            if ($delta > 0) {
                self::move(
                    $ticket,
                    (int) $productId,
                    'exit',
                    'repair',
                    $delta,
                    null,
                    'Stock re-consumed: repair ' . ($ticket->ticket_number ?? $ticket->getKey()) . ' restored'
                );
            }
        }
    }
}
