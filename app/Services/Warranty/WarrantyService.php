<?php

namespace App\Services\Warranty;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warranty;
use Carbon\Carbon;

class WarrantyService
{
    /*
    |--------------------------------------------------------------------------
    | Activate warranties from all eligible items in a sale
    |--------------------------------------------------------------------------
    |
    | Eligible items:
    |   - motorcycle units (any item with motorcycle_unit_id)
    |   - products with has_warranty = true
    |   - products of types: trotinette, velo_electrique, velo_normal
    |
    | start_date = sale creation date
    | end_date   = start_date + warranty_duration from sale item (default 1 year)
    | status     = auto-derived by Warranty::getStatusAttribute from end_date
    |
    */

    public static function activateFromSale(Sale $sale): void
    {
        $sale->loadMissing([
            'items.product',
            'items.motorcycleUnit.motorcycleModel',
        ]);

        // Use the actual sale date as the warranty start.
        $startDate = Carbon::parse($sale->created_at)->startOfDay();

        foreach ($sale->items as $item) {
            if (! self::itemRequiresWarranty($item)) {
                continue;
            }

            $endDate = self::resolveEndDate(
                $startDate,
                $item->warranty_duration_value,
                $item->warranty_duration_unit
            );

            Warranty::firstOrCreate(
                [
                    'sale_id'            => $sale->id,
                    'motorcycle_unit_id' => $item->motorcycle_unit_id ?? null,
                    'product_id'         => $item->motorcycle_unit_id ? null : ($item->product_id ?? null),
                ],
                [
                    'client_id'          => $sale->client_id,
                    'motorcycle_id'      => $item->motorcycle_id ?? null,
                    'start_date'         => $startDate->toDateString(),
                    'end_date'           => $endDate->toDateString(),
                    'warranty_kilometers' => $item->warranty_kilometers ?? null,
                    'notes'              => self::buildNotes($item),
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy single-item activation (kept for backward-compat)
    |--------------------------------------------------------------------------
    */

    public static function activate(Sale $sale, $subject): Warranty
    {
        $startDate = Carbon::parse($sale->created_at)->startOfDay();

        return Warranty::firstOrCreate(
            [
                'sale_id'       => $sale->id,
                'motorcycle_id' => $subject->id,
            ],
            [
                'client_id'  => $sale->client_id,
                'start_date' => $startDate->toDateString(),
                'end_date'   => $startDate->copy()->addYear()->toDateString(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private static function itemRequiresWarranty(SaleItem $item): bool
    {
        if ($item->motorcycle_unit_id) {
            return true;
        }

        if ($item->motorcycle_id) {
            return true;
        }

        if (! $item->product) {
            return false;
        }

        if (\in_array($item->product->type, [
            'motorcycle',
            'trotinette',
            'velo_electrique',
            'velo_normal',
        ], true)) {
            return true;
        }

        return (bool) $item->product->has_warranty;
    }

    private static function resolveEndDate(
        Carbon $startDate,
        ?int $durationValue,
        ?string $durationUnit
    ): Carbon {
        if (! $durationValue || $durationValue <= 0) {
            return $startDate->copy()->addYear();
        }

        return match ($durationUnit) {
            'days'   => $startDate->copy()->addDays($durationValue),
            'weeks'  => $startDate->copy()->addWeeks($durationValue),
            'months' => $startDate->copy()->addMonths($durationValue),
            'years'  => $startDate->copy()->addYears($durationValue),
            default  => $startDate->copy()->addYear(),
        };
    }

    private static function buildNotes(SaleItem $item): ?string
    {
        if ($item->motorcycle_unit_id && $item->motorcycleUnit) {
            $chassis = $item->motorcycleUnit->chassis_number;
            $model   = $item->motorcycleUnit->motorcycleModel;

            return trim(
                ($model ? ($model->marque . ' ' . $model->modele . ' — ') : '')
                . ($chassis ? 'chassis: ' . $chassis : '')
            );
        }

        if ($item->product) {
            return $item->product->name
                . ($item->product->sku ? ' (' . $item->product->sku . ')' : '');
        }

        return null;
    }
}
