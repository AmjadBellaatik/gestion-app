<?php

namespace App\Services\Reconciliation;

use App\Models\Payment;
use App\Models\RepairTicket;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\Warranty;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    /*
    |--------------------------------------------------------------------------
    | Entry point — run all reconcilers for a company
    |--------------------------------------------------------------------------
    */

    public function reconcileCompany(int $companyId): array
    {
        return [
            'sale_totals'          => $this->reconcileSaleTotals($companyId),
            'payment_status'       => $this->reconcilePaymentStatus($companyId),
            'repair_ticket_status' => $this->reconcileRepairTicketStatus($companyId),
            'reseller_balances'    => $this->reconcileResellerBalances($companyId),
            'warranties'           => $this->reconcileWarranties($companyId),
            'stock_movements'      => $this->reconcileStockMovements($companyId),
            'transactions'         => $this->reconcilePaymentTransactions($companyId),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 1 — Sale totals
    | Fix sales where total = 0 but items or paid_amount exist.
    |--------------------------------------------------------------------------
    */

    public function reconcileSaleTotals(int $companyId): int
    {
        $count = 0;

        Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(fn ($q) => $q->where('total', 0)->orWhere('subtotal', 0))
            ->whereHas('items')
            ->with('items')
            ->each(function (Sale $sale) use (&$count) {
                $itemsTotal = $sale->items->sum(fn ($i) => (float) $i->total);

                // Items have no prices but a paid_amount exists — backfill proportionally
                if ($itemsTotal <= 0 && (float) $sale->paid_amount > 0) {
                    $paidAmount  = (float) $sale->paid_amount;
                    $itemCount   = max(1, $sale->items->count());
                    $perItem     = round($paidAmount / $itemCount, 2);

                    foreach ($sale->items as $item) {
                        $qty       = max(1.0, (float) $item->quantity);
                        $unitPrice = round($perItem / $qty, 2);
                        $item->updateQuietly([
                            'unit_price' => $unitPrice,
                            'total'      => round($unitPrice * $qty, 2),
                            'tax'        => round($unitPrice * $qty * (20 / 120), 2),
                        ]);
                    }

                    $itemsTotal = $paidAmount;
                }

                if ($itemsTotal <= 0) {
                    return;
                }

                // Respect any admin discount already stored on the sale
                $discount  = max(0.0, (float) $sale->discount);
                $netTotal  = max(0.0, $itemsTotal - $discount);
                $tax       = round($netTotal * (20 / 120), 2);
                $subtotal  = round($netTotal - $tax, 2);
                $remaining = max(0, round($netTotal - (float) $sale->paid_amount, 2));

                $sale->updateQuietly([
                    'subtotal'         => $subtotal,
                    'tax'              => $tax,
                    'total'            => round($netTotal, 2),
                    'remaining_amount' => $remaining,
                ]);

                $count++;
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | 2 — Payment status
    | Recompute paid_amount / remaining_amount / payment_status from actual
    | paid payments so stale values are corrected.
    |--------------------------------------------------------------------------
    */

    public function reconcilePaymentStatus(int $companyId): int
    {
        $count = 0;

        Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('total', '>', 0)
            // Either currently has a paid payment, OR its stored paid_amount is
            // stuck nonzero (e.g. all paid payments were later deleted/reversed
            // and the stored aggregate never caught up) — both need checking.
            ->where(fn ($q) => $q
                ->whereHas('payments', fn ($q2) => $q2->withoutGlobalScopes()->whereNull('deleted_at')->where('status', 'paid'))
                ->orWhere('paid_amount', '>', 0))
            // withoutGlobalScopes() on the Payment relation strips its
            // SoftDeletingScope too — a soft-deleted payment would otherwise
            // still be summed here, which is exactly what made this reconciler
            // report "0 fixed" for a sale whose only payment had been deleted.
            ->with(['payments' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')->where('status', 'paid')])
            ->each(function (Sale $sale) use (&$count) {
                $totalPaid = (float) $sale->payments->sum('amount');
                $total     = (float) $sale->total;
                $remaining = max(0, $total - $totalPaid);

                $expectedStatus = match (true) {
                    $totalPaid <= 0       => 'unpaid',
                    $remaining <= 0       => 'paid',
                    default               => 'partial',
                };

                if (
                    abs((float) $sale->paid_amount - $totalPaid) > 0.01
                    || $sale->payment_status !== $expectedStatus
                    || abs((float) $sale->remaining_amount - $remaining) > 0.01
                ) {
                    $sale->updateQuietly([
                        'paid_amount'      => $totalPaid,
                        'remaining_amount' => $remaining,
                        'payment_status'   => $expectedStatus,
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | 2b — Repair ticket payment status
    | Same drift-correction as sales, applied to repair_tickets.
    |--------------------------------------------------------------------------
    */

    public function reconcileRepairTicketStatus(int $companyId): int
    {
        $count = 0;

        RepairTicket::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('total_cost', '>', 0)
            ->where(fn ($q) => $q
                ->whereHas('payments', fn ($q2) => $q2->withoutGlobalScopes()->whereNull('deleted_at')->where('status', 'paid'))
                ->orWhere('paid_amount', '>', 0))
            ->with(['payments' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')->where('status', 'paid')])
            ->each(function (RepairTicket $ticket) use (&$count) {
                $totalPaid = (float) $ticket->payments->sum('amount');
                $total     = (float) $ticket->total_cost;
                $remaining = max(0, $total - $totalPaid);

                $expectedStatus = match (true) {
                    $totalPaid <= 0 => 'unpaid',
                    $remaining <= 0 => 'paid',
                    default         => 'partial',
                };

                if (
                    abs((float) $ticket->paid_amount - $totalPaid) > 0.01
                    || $ticket->payment_status !== $expectedStatus
                    || abs((float) $ticket->remaining_amount - $remaining) > 0.01
                ) {
                    $ticket->updateQuietly([
                        'paid_amount'      => $totalPaid,
                        'remaining_amount' => $remaining,
                        'payment_status'   => $expectedStatus,
                        'paid_at'          => $expectedStatus === 'paid' ? ($ticket->paid_at ?? now()) : null,
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | 2c — Reseller balances
    | Mirrors Reseller::recalculate() (total_orders / total_paid / current_debt)
    | so a reseller whose recalculation was ever missed self-heals here too.
    |--------------------------------------------------------------------------
    */

    public function reconcileResellerBalances(int $companyId): int
    {
        $count = 0;

        Reseller::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->each(function (Reseller $reseller) use (&$count) {
                $saleIds = Sale::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where('reseller_id', $reseller->id)
                    ->pluck('id');

                $totalOrders = $saleIds->count();

                // withoutGlobalScopes() strips Payment's SoftDeletingScope too,
                // so a deleted payment would otherwise still be summed here.
                $totalPaid = Payment::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->whereIn('sale_id', $saleIds)
                    ->where('status', 'paid')
                    ->sum('amount');

                $totalSaleAmount = Sale::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->whereIn('id', $saleIds)
                    ->sum('total');

                $expectedDebt = max(0, $totalSaleAmount - $totalPaid);

                if (
                    (int) $reseller->total_orders !== $totalOrders
                    || abs((float) $reseller->total_paid - $totalPaid) > 0.01
                    || abs((float) $reseller->current_debt - $expectedDebt) > 0.01
                ) {
                    $reseller->updateQuietly([
                        'total_orders' => $totalOrders,
                        'total_paid'   => $totalPaid,
                        'current_debt' => $expectedDebt,
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | 3 — Warranties
    | Create missing warranty records for sales that have warranty-eligible items.
    |--------------------------------------------------------------------------
    */

    public function reconcileWarranties(int $companyId): int
    {
        $count = 0;

        Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')                              // never recreate for soft-deleted sales
            ->whereHas('items', fn ($q) => $q
                ->whereNotNull('motorcycle_unit_id')
                ->orWhereHas('product', fn ($pq) => $pq
                    ->where('has_warranty', true)
                    ->orWhereIn('type', ['motorcycle', 'trotinette', 'velo_electrique', 'velo_normal'])
                )
            )
            ->whereDoesntHave('warranties', fn ($q) => $q->withoutGlobalScopes()) // count soft-deleted warranties too — if user deleted one, respect it
            ->with('items.product', 'items.motorcycleUnit.motorcycleModel')
            ->each(function (Sale $sale) use ($companyId, &$count) {
                $this->createWarrantiesForSale($sale, $companyId);
                $count++;
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | 4 — Stock movements
    | Create missing exit movements for sale items that have a product.
    |--------------------------------------------------------------------------
    */

    public function reconcileStockMovements(int $companyId): int
    {
        $count = 0;

        SaleItem::withoutGlobalScopes()
            ->whereNotNull('product_id')
            ->whereHas('sale', fn ($q) => $q->withoutGlobalScopes()->where('company_id', $companyId))
            ->whereNotExists(fn ($q) => $q
                ->select(DB::raw(1))
                ->from('stock_movements')
                ->where('stock_movements.reference_type', Sale::class)
                ->whereColumn('stock_movements.reference_id', 'sale_items.sale_id')
                ->whereColumn('stock_movements.product_id', 'sale_items.product_id')
                ->where('stock_movements.movement_type', 'sale')
            )
            ->with('sale')
            ->each(function (SaleItem $item) use ($companyId, &$count) {
                if (! $item->sale) {
                    return;
                }

                StockMovement::withoutGlobalScopes()->create([
                    'company_id'     => $companyId,
                    'product_id'     => $item->product_id,
                    'movement_type'  => 'sale',
                    'type'           => 'exit',
                    'quantity'       => max(1, (float) $item->quantity),
                    'unit_cost'      => (float) $item->unit_price,
                    'reference'      => $item->sale->sale_number,
                    'reference_type' => Sale::class,
                    'reference_id'   => $item->sale_id,
                    'notes'          => 'Auto-reconciled: Sale #' . $item->sale->sale_number,
                    'user_id'        => null,
                ]);

                $count++;
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | 5 — Payment transactions
    | Create missing ledger Transaction records for paid payments.
    |--------------------------------------------------------------------------
    */

    public function reconcilePaymentTransactions(int $companyId): int
    {
        $count = 0;

        Payment::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereNull('deleted_at')
            ->whereNotExists(fn ($q) => $q
                ->select(DB::raw(1))
                ->from('transactions')
                ->where('transactions.reference_type', Payment::class)
                ->whereColumn('transactions.reference_id', 'payments.id')
            )
            ->each(function (Payment $payment) use ($companyId, &$count) {
                $type = $payment->sale_id ? 'sale_payment' : ($payment->repair_ticket_id ? 'repair_payment' : 'other_payment');

                Transaction::withoutGlobalScopes()->create([
                    'company_id'       => $companyId,
                    'type'             => $type,
                    'category'         => $type,
                    'amount'           => $payment->amount,
                    'direction'        => 'income',
                    'reference_type'   => Payment::class,
                    'reference_id'     => $payment->id,
                    'payment_method'   => $payment->payment_method,
                    'status'           => in_array($payment->payment_method, ['cash', 'card'], true) ? 'validated' : 'pending',
                    'description'      => $payment->sale_id
                        ? 'Payment for sale #' . $payment->sale_id
                        : 'Payment #' . $payment->id,
                    'transaction_date' => $payment->created_at ?? now(),
                    'user_id'          => null,
                ]);

                $count++;
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | Private helpers
    |--------------------------------------------------------------------------
    */

    private function createWarrantiesForSale(Sale $sale, int $companyId): void
    {
        // Warranty start must use the user-entered sale date.
        // Skip rather than silently fall back to created_at on historical records.
        if (! $sale->sale_date) {
            return;
        }

        $startDate = Carbon::parse($sale->sale_date)->startOfDay();

        foreach ($sale->items as $item) {
            if (! $this->itemNeedsWarranty($item)) {
                continue;
            }

            $endDate = $this->resolveWarrantyEndDate(
                $startDate,
                $item->warranty_duration_value,
                $item->warranty_duration_unit
            );

            Warranty::withoutGlobalScopes()->updateOrCreate(
                [
                    'sale_id'            => $sale->id,
                    'motorcycle_unit_id' => $item->motorcycle_unit_id ?? null,
                    'product_id'         => $item->motorcycle_unit_id ? null : ($item->product_id ?? null),
                ],
                [
                    'company_id'          => $companyId,
                    'client_id'           => $sale->client_id,
                    'motorcycle_id'       => $item->motorcycle_id ?? null,
                    'start_date'          => $startDate->toDateString(),
                    'end_date'            => $endDate->toDateString(),
                    'warranty_kilometers' => $item->warranty_kilometers ?? null,
                    'notes'               => $this->buildWarrantyNote($item),
                ]
            );
        }
    }

    private function itemNeedsWarranty(SaleItem $item): bool
    {
        if ($item->motorcycle_unit_id || $item->motorcycle_id) {
            return true;
        }

        if (! $item->product) {
            return false;
        }

        return in_array($item->product->type, ['motorcycle', 'trotinette', 'velo_electrique', 'velo_normal'], true)
            || (bool) $item->product->has_warranty;
    }

    private function resolveWarrantyEndDate(Carbon $start, ?int $value, ?string $unit): Carbon
    {
        if (! $value || $value <= 0) {
            return $start->copy()->addYear();
        }

        return match ($unit) {
            'days'   => $start->copy()->addDays($value),
            'weeks'  => $start->copy()->addWeeks($value),
            'months' => $start->copy()->addMonths($value),
            'years'  => $start->copy()->addYears($value),
            default  => $start->copy()->addYear(),
        };
    }

    private function buildWarrantyNote(SaleItem $item): ?string
    {
        if ($item->motorcycle_unit_id && $item->motorcycleUnit) {
            $model = $item->motorcycleUnit->motorcycleModel;
            return trim(
                ($model ? $model->marque . ' ' . $model->modele . ' — ' : '')
                . ($item->motorcycleUnit->chassis_number ? 'chassis: ' . $item->motorcycleUnit->chassis_number : '')
            );
        }

        if ($item->product) {
            return $item->product->name . ($item->product->sku ? ' (' . $item->product->sku . ')' : '');
        }

        return null;
    }
}
