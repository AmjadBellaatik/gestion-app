<?php

namespace App\Services\Sales;

use DB;

use App\Models\Sale;
use App\Models\Payment;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\MotorcycleUnit;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TreasuryTransaction;
use App\Models\Warranty;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\GeneratedPdf;
use App\Models\ChequePayment;
use App\Models\BankTransferPayment;

use App\Services\Documents\DocumentService;
use App\Services\Warranty\WarrantyService;
use App\Services\Accounting\AccountingService;
use Illuminate\Support\Facades\Schema;

class SaleService
{
    public static function cleanupRelatedRecordsForDeletion(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $saleId = $sale->getKey();

            $saleItems = SaleItem::query()
                ->where('sale_id', $saleId)
                ->get(['id', 'product_id', 'motorcycle_unit_id']);

            $productIds = $saleItems
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->values();

            $motorcycleUnitIds = $saleItems
                ->pluck('motorcycle_unit_id')
                ->filter()
                ->unique()
                ->values();

            $documentIds = Document::withTrashed()
                ->withoutGlobalScopes()
                ->where('sale_id', $saleId)
                ->pluck('id');

            $paymentIds = Payment::withTrashed()
                ->withoutGlobalScopes()
                ->where(function ($query) use ($saleId, $documentIds): void {
                    $query->where('sale_id', $saleId);

                    if ($documentIds->isNotEmpty()) {
                        $query->orWhereIn('document_id', $documentIds);
                    }
                })
                ->pluck('id');

            if ($paymentIds->isNotEmpty()) {
                ChequePayment::query()
                    ->whereIn('payment_id', $paymentIds)
                    ->delete();

                BankTransferPayment::query()
                    ->whereIn('payment_id', $paymentIds)
                    ->delete();

                TreasuryTransaction::withoutGlobalScopes()
                    ->whereIn('payment_id', $paymentIds)
                    ->delete();

                Transaction::withoutGlobalScopes()
                    ->where('reference_type', Payment::class)
                    ->whereIn('reference_id', $paymentIds)
                    ->delete();

                Payment::withTrashed()
                    ->withoutGlobalScopes()
                    ->whereIn('id', $paymentIds)
                    ->get()
                    ->each(fn (Payment $payment) => $payment->trashed() ? null : $payment->delete());
            }

            if ($documentIds->isNotEmpty()) {
                StockMovement::withoutGlobalScopes()
                    ->where('reference_type', Document::class)
                    ->whereIn('reference_id', $documentIds)
                    ->delete();

                GeneratedPdf::query()
                    ->whereIn('document_id', $documentIds)
                    ->delete();

                Document::withTrashed()
                    ->withoutGlobalScopes()
                    ->whereIn('id', $documentIds)
                    ->get()
                    ->each(function (Document $document): void {
                        $document->items()->delete();

                        if (! $document->trashed()) {
                            $document->delete();
                        }
                    });
            }

            StockMovement::withoutGlobalScopes()
                ->where(function ($query) use ($sale, $saleId): void {
                    $query
                        ->where(function ($subQuery) use ($saleId): void {
                            $subQuery
                                ->where('reference_type', Sale::class)
                                ->where('reference_id', $saleId);
                        })
                        ->orWhere(function ($subQuery) use ($sale): void {
                            $subQuery
                                ->where('movement_type', 'sale')
                                ->where('reference', $sale->sale_number);
                        });
                })
                ->delete();

            Transaction::withoutGlobalScopes()
                ->where('reference_type', Sale::class)
                ->where('reference_id', $saleId)
                ->delete();

            Warranty::withoutGlobalScopes()
                ->where('sale_id', $saleId)
                ->delete();

            SaleItem::query()
                ->where('sale_id', $saleId)
                ->delete();

            if ($motorcycleUnitIds->isNotEmpty()) {
                if ($documentIds->isNotEmpty()) {
                    MotorcycleUnit::withoutGlobalScopes()
                        ->whereIn('id', $motorcycleUnitIds)
                        ->whereIn('document_id', $documentIds)
                        ->update(['document_id' => null]);
                }

                MotorcycleUnit::withoutGlobalScopes()
                    ->whereIn('id', $motorcycleUnitIds)
                    ->update([
                        'client_id' => null,
                        'sale_date' => null,
                        'status' => 'in_stock',
                    ]);
            }

            if ($productIds->isNotEmpty() && Schema::hasColumn('products', 'status')) {
                Product::withoutGlobalScopes()
                    ->whereIn('id', $productIds)
                    ->get()
                    ->each(function (Product $product): void {
                        if ((float) $product->current_stock > 0) {
                            $product->update(['status' => 'in_stock']);
                        }
                    });
            }
        });
    }

    public static function create(
        array $data
    ): Sale {

        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | 1 — CREATE SALE
            |--------------------------------------------------------------------------
            */

            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'cash';

            $sale = Sale::create([

                'client_id' => $data['client_id'] ?? null,

                'reseller_id' => $data['reseller_id'] ?? null,

                'sale_number' => self::generateSaleNumber(),

                'sale_type' => $data['sale_type'] ?? 'direct',

                'subtotal' => 0,

                'discount' => 0,

                'tax' => 0,

                'total' => 0,

                // Start at 0 — the Payment observer will credit paid_amount correctly
                // after the payment record is created in step 7 below.
                'paid_amount' => 0,

                'remaining_amount' => 0,

                'payment_status' => 'unpaid',

                'status' => 'completed',

                'notes' => $data['notes'] ?? null,

            ]);

            /*
            |--------------------------------------------------------------------------
            | 2 — ACCOUNTING AUTOMATION
            |--------------------------------------------------------------------------
            |
            | Deferred until totals are computed from sale items.
            |
            */

            /*
            |--------------------------------------------------------------------------
            | 3 — CREATE SALE ITEMS
            |--------------------------------------------------------------------------
            */

            $items = [];
            $totalIncludingTax = 0;

            $saleInputItems = $data['items'] ?? $data['saleItems'] ?? [];

            foreach ($saleInputItems as $item) {
                $quantity = ! empty($item['motorcycle_unit_id']) ? 1.0 : (float) ($item['quantity'] ?? 1);

                // Prefer price from form (auto-filled by the UI via afterStateUpdated).
                // Fall back to DB resolution if the form sent 0 (e.g. model has no price set).
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                if ($unitPrice <= 0) {
                    if (! empty($item['motorcycle_unit_id'])) {
                        $motorcycleUnit = MotorcycleUnit::query()
                            ->with('motorcycleModel')
                            ->find($item['motorcycle_unit_id']);

                        $unitPrice = self::resolveMotorcycleModelSalePrice(
                            $motorcycleUnit?->motorcycleModel,
                            ! empty($data['reseller_id'])
                        );
                    } elseif (! empty($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        $unitPrice = self::resolveProductSalePrice(
                            $product,
                            ! empty($data['reseller_id'])
                        );
                    }
                }

                $lineTotal = round($quantity * $unitPrice, 2);
                $lineTax = round($lineTotal * (20 / 120), 2);
                $totalIncludingTax += $lineTotal;

                $saleItem = SaleItem::create([

                    'sale_id' => $sale->id,

                    'product_id' => $item['product_id'] ?? null,
                    'motorcycle_unit_id' => $item['motorcycle_unit_id'] ?? null,

                    'quantity' => $quantity,

                    'unit_price' => $unitPrice,

                    'discount' => (float) ($item['discount'] ?? 0),

                    'tax' => $lineTax,

                    'total' => $lineTotal,

                    'warranty_duration_value' => $item['warranty_duration_value'] ?? null,
                    'warranty_duration_unit' => $item['warranty_duration_unit'] ?? null,
                    'warranty_kilometers' => $item['warranty_kilometers'] ?? null,

                ]);

                $items[] = $saleItem;

                /*
                |--------------------------------------------------------------------------
                | PRODUCT
                |--------------------------------------------------------------------------
                */

                $product = null;

                if (! empty($item['product_id'])) {

                    $product = Product::find(

                        $item['product_id']

                    );

                }

                /*
                |--------------------------------------------------------------------------
                | 4 — STOCK MOVEMENT
                |--------------------------------------------------------------------------
                */

                static::ensureProductIdNullable();

                StockMovement::create([

                    'company_id'         => session('company_id'),

                    'product_id'         => $item['product_id'] ?? null,

                    'motorcycle_unit_id' => $item['motorcycle_unit_id'] ?? null,

                    'movement_type'      => 'sale',

                    'type'               => 'exit',

                    'quantity'           => ! empty($item['motorcycle_unit_id']) ? 1 : $item['quantity'],

                    'unit_cost'          => $unitPrice,

                    'reference'          => $sale->sale_number,

                    'reference_type'     => Sale::class,

                    'reference_id'       => $sale->id,

                    'notes'              => 'Sale #'.$sale->sale_number,

                    'user_id'            => auth()->id(),

                ]);

                /*
                |--------------------------------------------------------------------------
                | 5 — MOTORCYCLE AUTOMATION
                |--------------------------------------------------------------------------
                */

                if (! empty($item['motorcycle_unit_id'])) {
                    self::handleMotorcycleUnitSale(
                        $sale,
                        (int) $item['motorcycle_unit_id']
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 6 — WARRANTY ACTIVATION
                |--------------------------------------------------------------------------
                */

                if (

                    $product &&

                    $product->type === 'motorcycle'

                ) {

                    WarrantyService::activate(

                        $sale,

                        $product

                    );

                }

            }

            // When item prices are all 0 (e.g. motorcycle model has no price_ttc set),
            // treat the paid_amount as the TTC total and backfill item prices from it.
            if ($totalIncludingTax <= 0 && $paidAmount > 0 && count($items) > 0) {
                $totalIncludingTax = $paidAmount;
                $perItem = round($paidAmount / count($items), 2);

                foreach ($items as $saleItem) {
                    $itemQty = max(1.0, (float) $saleItem->quantity);
                    $itemUnitPrice = round($perItem / $itemQty, 2);
                    $saleItem->update([
                        'unit_price' => $itemUnitPrice,
                        'total'      => round($itemUnitPrice * $itemQty, 2),
                        'tax'        => round($itemUnitPrice * $itemQty * (20 / 120), 2),
                    ]);
                }
            }

            // Sum per-item discounts (clamp to gross total so it can't go negative)
            $discount = max(0.0, min(
                collect($saleInputItems)->sum(fn ($item) => max(0.0, (float) ($item['discount'] ?? 0))),
                $totalIncludingTax
            ));
            $netTotal     = max(0.0, $totalIncludingTax - $discount);
            $saleTax      = round($netTotal * (20 / 120), 2);
            $saleSubtotal = round($netTotal - $saleTax, 2);

            // Set totals only. paid_amount / remaining_amount / payment_status
            // will be updated by the Payment observer after step 7.
            // Do NOT set payment_status here to avoid triggering Sale::updated
            // auto-payment when no payment record exists yet.
            $sale->update([
                'subtotal'         => $saleSubtotal,
                'tax'              => $saleTax,
                'discount'         => $discount,
                'discount_note'    => $data['discount_note'] ?? null,
                'total'            => round($netTotal, 2),
                'remaining_amount' => round($netTotal, 2),
                'payment_status'   => 'unpaid',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7 — PAYMENT
            |--------------------------------------------------------------------------
            */

            if ($paidAmount > 0) {

                // Let the Payment::creating observer assign the correct status
                // based on payment_method (cash/card → paid, cheque → pending_validation, etc.)
                $payment = Payment::create([
                    'sale_id'        => $sale->id,
                    'client_id'      => $sale->client_id,
                    'amount'         => $paidAmount,
                    'payment_method' => $paymentMethod,
                    'reference'      => $paymentMethod === 'cash'
                        ? null
                        : ($data['reference'] ?? $data['cheque_number'] ?? null),
                    'notes'          => 'Payment for sale ' . $sale->sale_number,
                ]);

                // Create cheque sub-record when relevant fields are present
                if ($paymentMethod === 'cheque' && ! empty($data['cheque_number'])) {
                    ChequePayment::create([
                        'payment_id'    => $payment->id,
                        'cheque_number' => $data['cheque_number'],
                        'bank_name'     => $data['bank_name'] ?? null,
                        'due_date'      => $data['cheque_due_date'] ?? null,
                        'status'        => 'received',
                    ]);
                }

                // Create bank-transfer sub-record when relevant fields are present
                if ($paymentMethod === 'bank_transfer' && ! empty($data['bank_name'])) {
                    BankTransferPayment::create([
                        'payment_id'       => $payment->id,
                        'bank_name'        => $data['bank_name'] ?? null,
                        'reference_number' => $data['transfer_reference'] ?? ($data['reference'] ?? null),
                        'transfer_date'    => $data['transfer_date'] ?? now()->toDateString(),
                        'status'           => 'sent',
                    ]);
                }

            }

            /*
            |--------------------------------------------------------------------------
            | 8 — ACCOUNTING TRANSACTION
            |--------------------------------------------------------------------------
            */

            $sale->refresh();

            Transaction::create([

                'company_id' => session('company_id'),

                'type' => 'sale',

                'reference_type' => Sale::class,

                'reference_id' => $sale->id,

                'amount' => $sale->total,

                'payment_method' =>

                    $paymentMethod,

                'description' =>

                    'Sale '.$sale->sale_number,

                'transaction_date' => now(),

                'user_id' => auth()->id(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | 9 — AUTO DOCUMENT GENERATION (SELECTED TYPES)
            |--------------------------------------------------------------------------
            */

            $selectedDocumentCodes = array_values(array_filter(
                $data['auto_document_codes'] ?? []
            ));

            self::generateSelectedDocumentsFromSale(
                $sale,
                $items,
                $selectedDocumentCodes
            );

            /*
            |--------------------------------------------------------------------------
            | 10 — AUTO WARRANTY ACTIVATION
            |--------------------------------------------------------------------------
            */

            WarrantyService::activateFromSale(
                $sale
            );

            /*
            |--------------------------------------------------------------------------
            | 11 — LEGACY DOCUMENT GENERATION DISABLED
            |--------------------------------------------------------------------------
            */

            /*
            |--------------------------------------------------------------------------
            | 12 — STATUS AUTOMATION
            |--------------------------------------------------------------------------
            */

            $sale->update([

                'status' => 'completed',

            ]);

            // NOTE: payment_status / paid_amount / remaining_amount are managed
            // exclusively by the Payment model observer (PaymentService::applyPayment).
            // Do NOT override them here to prevent double-counting.

            return $sale;

        });

    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT GENERATION
    |--------------------------------------------------------------------------
    */

    protected static function generateInvoice(
        Sale $sale
    ): void {

        Document::create([

            'company_id' => session('company_id'),

            'client_id' => $sale->client_id,

            'number' => 'INV-'.$sale->sale_number,

            'status' => 'validated',

            'total' => $sale->total,

            'notes' => 'Generated from sale',

        ]);

    }

    protected static function generateBL(
        Sale $sale
    ): void {

        Document::create([

            'company_id' => session('company_id'),

            'client_id' => $sale->client_id,

            'number' => 'BL-'.$sale->sale_number,

            'status' => 'validated',

            'total' => 0,

            'notes' => 'Bon de livraison',

        ]);

    }

    /** @deprecated — conformity is now generated via DocumentService::generate() */
    protected static function generateConformity(
        Sale $sale,
        object $motorcycle
    ): void {

        Document::create([

            'company_id' => session('company_id'),

            'client_id' => $sale->client_id,

            'motorcycle_id' => $motorcycle->id,

            'number' => 'CONF-'.$sale->sale_number,

            'status' => 'validated',

            'total' => 0,

            'notes' => 'Conformity document',

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | SALE NUMBER
    |--------------------------------------------------------------------------
    */

    protected static function generateSaleNumber(): string
    {
        return 'SAL-'.now()->format('YmdHis');
    }

    protected static function handleMotorcycleUnitSale(
        Sale $sale,
        int $motorcycleUnitId
    ): void {
        $unit = MotorcycleUnit::find($motorcycleUnitId);

        if (! $unit) {
            return;
        }

        // Unit goes on_hold until payment is fully validated.
        // PaymentService::applyPayment() will transition to 'sold' when paid.
        $unit->update([
            'client_id' => $sale->client_id,
            'sale_date' => now()->toDateString(),
            'status'    => 'on_hold',
        ]);
    }

    public static function generateSelectedDocumentsFromSale(
        Sale $sale,
        array $saleItems,
        array $selectedCodes
    ): void {
        if (empty($selectedCodes)) {
            return;
        }

        $documentTypes = DocumentType::query()
            ->whereIn('code', $selectedCodes)
            ->where('is_active', true)
            ->get()
            ->keyBy('code');

        $motorcycleUnit = self::resolveSaleMotorcycleUnit($sale, $saleItems);
        $warrantySaleItem = self::resolveWarrantySaleItem($saleItems);
        $hasReseller = filled($sale->reseller_id);

        foreach ($selectedCodes as $code) {
            $type = $documentTypes->get($code);

            if (! $type) {
                continue;
            }

            $isConformity = $code === DocumentType::CONFORMITY;
            $isWarranty = $code === DocumentType::WARRANTY_CONTRACT;

            if ($hasReseller && $isWarranty) {
                continue;
            }

            if ($isConformity && ! $motorcycleUnit) {
                continue;
            }

            $documentItems = $isConformity
                ? [[
                    'item_type' => 'motorcycle',
                    'motorcycle_unit_id' => $motorcycleUnit?->id,
                    'quantity' => 1,
                    'unit_price' => 0,
                    'discount_amount' => 0,
                ]]
                : ($isWarranty && $warrantySaleItem
                    ? [[
                        'item_type' => $warrantySaleItem->motorcycle_unit_id
                            ? 'motorcycle'
                            : ($warrantySaleItem->product?->type ?: 'product'),
                        'product_id' => $warrantySaleItem->product_id,
                        'motorcycle_unit_id' => $warrantySaleItem->motorcycle_unit_id,
                        'quantity' => (float) $warrantySaleItem->quantity,
                        'unit_price' => 0,
                        'discount_amount' => 0,
                    ]]
                : collect($saleItems)->map(fn (SaleItem $item) => [
                    'item_type' => $item->motorcycle_unit_id ? 'motorcycle' : 'product',
                    'product_id' => $item->product_id,
                    'motorcycle_unit_id' => $item->motorcycle_unit_id,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => self::resolveCommercialSaleItemUnitPrice(
                        $sale,
                        $item,
                        count($saleItems)
                    ),
                    'discount_amount' => (float) ($item->discount ?? 0),
                ])->values()->all());

            if ($isWarranty && ! $warrantySaleItem) {
                continue;
            }

            DB::transaction(function () use ($sale, $type, $code, $isConformity, $isWarranty, $warrantySaleItem, $documentItems): void {
                $previousNumber = Document::query()
                    ->where('sale_id', $sale->id)
                    ->where('document_type_id', $type->id)
                    ->orderByDesc('id')
                    ->value('document_number');

                // forceDelete: soft-delete would keep the row and block the unique index on document_number
                Document::withTrashed()
                    ->where('sale_id', $sale->id)
                    ->where('document_type_id', $type->id)
                    ->forceDelete();

                DocumentService::generate([
                    'document_type_id' => $type->id,
                    'client_id' => $sale->reseller_id ? null : $sale->client_id,
                    'reseller_id' => $sale->reseller_id,
                    'sale_id' => $sale->id,
                    'document_number' => $previousNumber,
                    'document_date' => now()->toDateString(),
                    'status' => 'generated',
                    'metadata' => $isWarranty ? [
                        'warranty_duration_value' => $warrantySaleItem?->warranty_duration_value,
                        'warranty_duration_unit' => $warrantySaleItem?->warranty_duration_unit,
                        'warranty_kilometers' => $warrantySaleItem?->warranty_kilometers,
                    ] : ($code === DocumentType::INVOICE && filled($sale->purchase_order_number) ? [
                        'purchase_order_number' => $sale->purchase_order_number,
                    ] : null),
                    'items' => $documentItems,
                ]);
            });
        }
    }

    protected static function resolveSaleMotorcycleUnit(Sale $sale, array $saleItems): ?MotorcycleUnit
    {
        $unitFromExistingDocs = $sale->documents()
            ->with('items')
            ->get()
            ->flatMap(fn (Document $document) => $document->items)
            ->first(fn ($item) => ! empty($item->motorcycle_unit_id));

        if ($unitFromExistingDocs?->motorcycle_unit_id) {
            return MotorcycleUnit::find($unitFromExistingDocs->motorcycle_unit_id);
        }

        $unitFromItems = collect($saleItems)->first(fn (SaleItem $item) => filled($item->motorcycle_unit_id));

        if ($unitFromItems?->motorcycle_unit_id) {
            return MotorcycleUnit::query()
                ->with('motorcycleModel')
                ->find($unitFromItems->motorcycle_unit_id);
        }

        if ($sale->client_id) {
            return MotorcycleUnit::query()
                ->where('client_id', $sale->client_id)
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    protected static function resolveWarrantySaleItem(array $saleItems): ?SaleItem
    {
        return collect($saleItems)
            ->first(function (SaleItem $item): bool {
                if ($item->motorcycle_unit_id) {
                    return true;
                }

                return in_array($item->product?->type, [
                    'trotinette',
                    'velo_electrique',
                    'velo_normal',
                ], true) || (bool) $item->product?->has_warranty;
            });
    }

    protected static function resolveCommercialSaleItemUnitPrice(
        Sale $sale,
        SaleItem $item,
        int $itemsCount
    ): float {

        $unitPrice = (float) $item->unit_price;

        if ($unitPrice > 0) {
            return $unitPrice;
        }

        if ($item->motorcycle_unit_id) {
            $unitPrice = (float) ($item->motorcycleUnit?->motorcycleModel?->price_ttc ?? 0);
        }

        if ($unitPrice <= 0 && $item->product_id) {
            $unitPrice = self::resolveProductSalePrice(
                $item->product,
                (bool) $sale->reseller_id
            );
        }

        if ($unitPrice <= 0 && $itemsCount === 1) {
            $unitPrice = (float) $sale->total;

            if ($unitPrice <= 0) {
                $unitPrice = (float) $sale->paid_amount;
            }
        }

        return $unitPrice;
    }

    protected static function resolveProductSalePrice(?Product $product, bool $hasReseller): float
    {
        if (! $product) {
            return 0.0;
        }

        if ($hasReseller && (float) $product->reseller_price > 0) {
            return (float) $product->reseller_price;
        }

        return (float) $product->selling_price;
    }

    protected static function resolveMotorcycleModelSalePrice(?\App\Models\MotorcycleModel $model, bool $hasReseller): float
    {
        if (! $model) {
            return 0.0;
        }

        if ($hasReseller && (float) $model->reseller_price > 0) {
            return (float) $model->reseller_price;
        }

        return (float) $model->price_ttc;
    }

    private static function ensureProductIdNullable(): void
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $col = DB::selectOne("
            SELECT IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'stock_movements'
              AND COLUMN_NAME  = 'product_id'
        ");

        if ($col && $col->IS_NULLABLE !== 'YES') {
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'stock_movements'
                  AND COLUMN_NAME  = 'product_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            if ($fk) {
                DB::statement("ALTER TABLE `stock_movements` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
            DB::statement('ALTER TABLE `stock_movements` MODIFY `product_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `stock_movements` ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL');
        }

        $checked = true;
    }
}
