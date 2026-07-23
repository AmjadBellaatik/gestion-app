<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\BankTransferPayment;
use App\Models\ChequePayment;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\MotorcycleUnit;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\Sales\SaleService;
use App\Services\Stock\StockService;
use App\Services\Warranty\WarrantyService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\DB;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    /** New items added in this edit session — processed in afterSave. */
    protected array $pendingNewItems = [];

    /** Quantity changes on existing product lines — stock deltas applied in afterSave. */
    protected array $pendingQtyAdjustments = [];

    /** Payment-section field values submitted this save — reconciled onto the sale's latest Payment in afterSave. */
    protected ?array $pendingPaymentDetails = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_payment')
                ->label(__('messages.add_payment'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->modalHeading(__('messages.add_payment'))
                ->form([
                    TextInput::make('amount')
                        ->label(__('messages.amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->required()
                        ->default(fn () => max(0, (float) $this->getRecord()->remaining_amount)),
                    Select::make('payment_method')
                        ->label(__('messages.payment_method'))
                        ->options([
                            'cash'          => __('messages.cash'),
                            'card'          => __('messages.card'),
                            'cheque'        => __('messages.cheque'),
                            'bank_transfer' => __('messages.bank_transfer'),
                        ])
                        ->required()
                        ->default('cash')
                        ->live(),
                    TextInput::make('reference')
                        ->label(__('messages.reference'))
                        ->placeholder(__('messages.optional'))
                        ->visible(fn (callable $get) => in_array($get('payment_method'), ['card', 'cash'], true)),

                    // Cheque sub-record fields — required whenever this payment is by cheque,
                    // so a second (or third...) cheque payment added from the sale is
                    // never silently missing its ChequePayment row.
                    Section::make(__('messages.cheque_information'))
                        ->visible(fn (callable $get) => $get('payment_method') === 'cheque')
                        ->schema([
                            TextInput::make('cheque_number')
                                ->label(__('messages.cheque_number'))
                                ->required(fn (callable $get) => $get('payment_method') === 'cheque'),
                            Select::make('bank_name')
                                ->label(__('messages.bank_name'))
                                ->options(self::bankOptions())
                                ->searchable()
                                ->required(fn (callable $get) => $get('payment_method') === 'cheque'),
                            DatePicker::make('cheque_due_date')
                                ->label(__('messages.due_date'))
                                ->required(fn (callable $get) => $get('payment_method') === 'cheque'),
                        ])
                        ->columns(3),

                    // Bank-transfer sub-record fields — same rationale as cheque above.
                    Section::make(__('messages.bank_transfer_information'))
                        ->visible(fn (callable $get) => $get('payment_method') === 'bank_transfer')
                        ->schema([
                            TextInput::make('transfer_bank_name')
                                ->label(__('messages.bank_name'))
                                ->required(fn (callable $get) => $get('payment_method') === 'bank_transfer'),
                            TextInput::make('transfer_reference')
                                ->label(__('messages.reference_number'))
                                ->required(fn (callable $get) => $get('payment_method') === 'bank_transfer'),
                            DatePicker::make('transfer_date')
                                ->label(__('messages.transfer_date'))
                                ->default(now())
                                ->required(fn (callable $get) => $get('payment_method') === 'bank_transfer'),
                        ])
                        ->columns(3),

                    Textarea::make('notes')
                        ->label(__('messages.notes'))
                        ->rows(2)
                        ->placeholder(__('messages.optional')),
                ])
                ->action(function (array $data): void {
                    $sale = $this->getRecord();
                    $method = $data['payment_method'];

                    $payment = Payment::create([
                        'sale_id'        => $sale->id,
                        'client_id'      => $sale->client_id,
                        'amount'         => (float) $data['amount'],
                        'payment_method' => $method,
                        'reference'      => match ($method) {
                            'cheque'        => $data['cheque_number'] ?? null,
                            'bank_transfer' => $data['transfer_reference'] ?? null,
                            default         => filled($data['reference'] ?? null) ? $data['reference'] : null,
                        },
                        'notes'          => filled($data['notes'] ?? null) ? $data['notes'] : 'Payment for sale ' . $sale->sale_number,
                    ]);

                    if ($method === 'cheque' && filled($data['cheque_number'] ?? null)) {
                        ChequePayment::create([
                            'payment_id'    => $payment->id,
                            'cheque_number' => $data['cheque_number'],
                            'bank_name'     => $data['bank_name'] ?? null,
                            'due_date'      => $data['cheque_due_date'] ?? null,
                            'status'        => 'received',
                        ]);
                    }

                    if ($method === 'bank_transfer' && filled($data['transfer_bank_name'] ?? null)) {
                        BankTransferPayment::create([
                            'payment_id'       => $payment->id,
                            'bank_name'        => $data['transfer_bank_name'],
                            'reference_number' => $data['transfer_reference'] ?? null,
                            'transfer_date'    => $data['transfer_date'] ?? now()->toDateString(),
                            'status'           => 'sent',
                        ]);
                    }

                    Notification::make()
                        ->title(__('messages.payment_added'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['paid_amount', 'remaining_amount', 'payment_status']);
                })
                ->visible(fn () => (float) $this->getRecord()->remaining_amount > 0),

            DeleteAction::make()
                ->visible(fn () => SaleResource::isAdminUser()),

            ForceDeleteAction::make()
                ->visible(fn () => SaleResource::isAdminUser()),

            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $record->loadMissing(['saleItems.product', 'saleItems.motorcycleUnit']);

        $data['saleItems'] = $record->saleItems->map(function (SaleItem $item): array {
            if ($item->motorcycle_unit_id) {
                $itemType = 'motorcycle';
            } elseif ($item->product && in_array($item->product->type, ['trotinette', 'velo_electrique', 'velo_normal'], true)) {
                $itemType = $item->product->type;
            } else {
                $itemType = 'product';
            }

            return [
                '_sale_item_id'           => $item->id,
                'item_type'               => $itemType,
                'product_id'              => $item->product_id,
                'motorcycle_unit_id'      => $item->motorcycle_unit_id,
                'quantity'                => $item->quantity,
                'unit_price'              => $item->unit_price,
                'discount'                => $item->discount,
                'warranty_duration_value' => $item->warranty_duration_value,
                'warranty_duration_unit'  => $item->warranty_duration_unit ?? 'years',
                'warranty_kilometers'     => $item->warranty_kilometers,
            ];
        })->values()->all();

        // Reconcile a legacy mismatch between the sale-level discount (what the view
        // page shows, stored in sales.discount) and the per-line discounts. Older
        // sales can carry sales.discount = 199 while the line's discount is 0, so the
        // edit form showed 0 — and, worse, saving would roll that 0 back onto the
        // sale and wipe the real Remise. Surface the stored amount so it is both
        // visible and preserved on the next save.
        $saleDiscount    = round((float) $record->discount, 2);
        $lineDiscountSum = round(collect($data['saleItems'])->sum(fn ($row) => (float) ($row['discount'] ?? 0)), 2);

        if ($saleDiscount > 0 && $lineDiscountSum !== $saleDiscount && ! empty($data['saleItems'])) {
            if (count($data['saleItems']) === 1 || $lineDiscountSum === 0.0) {
                // Single line, or multiple lines with no per-line discount recorded:
                // attach the full sale discount to the first line so it is not lost.
                $data['saleItems'][0]['discount'] = $saleDiscount;
            }
        }

        // The payment fields are create-time inputs — payment_method and the
        // cheque / bank-transfer details live on the Payment record, not the sale.
        // On edit, surface the real values from the sale's most recent payment so
        // the form shows what is actually stored instead of a blank, "required"
        // method that looks erased. Editing the sale never rewrites payments (those
        // are managed by the Add-payment action / PaymentService), so this is
        // display context that is simply preserved on save.
        $payment = $record->payments()
            ->with(['chequePayment', 'bankTransferPayment'])
            ->latest('id')
            ->first();

        if ($payment) {
            $data['payment_method'] = $payment->payment_method;

            if ($payment->payment_method === 'card') {
                $data['reference'] = $payment->reference;
            }

            if ($payment->payment_method === 'cheque' && $payment->chequePayment) {
                $data['cheque_number']   = $payment->chequePayment->cheque_number;
                $data['bank_name']       = $payment->chequePayment->bank_name;
                $data['cheque_due_date'] = $payment->chequePayment->due_date;
            }

            if ($payment->payment_method === 'bank_transfer' && $payment->bankTransferPayment) {
                $data['bank_name']          = $payment->bankTransferPayment->bank_name;
                $data['transfer_reference'] = $payment->bankTransferPayment->reference_number;
                $data['transfer_date']      = $payment->bankTransferPayment->transfer_date;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $today = now()->toDateString();
        if (! SaleResource::isAdminUser()) {
            $data['sale_date'] = optional($this->getRecord()->sale_date)->toDateString();
        } elseif (filled($data['sale_date'] ?? null) && $data['sale_date'] > $today) {
            $data['sale_date'] = $today;
        }

        $this->pendingNewItems = [];
        $this->pendingQtyAdjustments = [];

        foreach ($data['saleItems'] ?? [] as $row) {
            $itemId = $row['_sale_item_id'] ?? null;

            if (! $itemId) {
                // New item added during edit — queue for creation in afterSave
                $this->pendingNewItems[] = $row;
                continue;
            }

            $existing = SaleItem::whereKey($itemId)
                ->where('sale_id', $this->getRecord()->id)
                ->first();

            if (! $existing) {
                continue;
            }

            $updates = [
                'discount'                => (float) ($row['discount'] ?? 0),
                'warranty_duration_value' => $row['warranty_duration_value'] ?? null,
                'warranty_duration_unit'  => $row['warranty_duration_unit'] ?? null,
                'warranty_kilometers'     => filled($row['warranty_kilometers'] ?? null) ? (int) $row['warranty_kilometers'] : null,
            ];

            // Quantity is editable only for stock products (motorcycle units are
            // always qty 1). When it changes, re-derive the line total/tax and queue
            // a compensating stock movement for the delta so stock stays accurate.
            // The form validator already caps the new quantity to available stock.
            if (! $existing->motorcycle_unit_id) {
                $newQty = max(0.0, (float) ($row['quantity'] ?? $existing->quantity));
                $oldQty = (float) $existing->quantity;

                if ($newQty > 0 && abs($newQty - $oldQty) > 0.0001) {
                    $unitPrice = (float) $existing->unit_price;
                    $lineTotal = round($newQty * $unitPrice, 2);

                    $updates['quantity'] = $newQty;
                    $updates['total']    = $lineTotal;
                    $updates['tax']      = round($lineTotal * (20 / 120), 2);

                    if ($existing->product_id) {
                        $this->pendingQtyAdjustments[] = [
                            'product_id' => (int) $existing->product_id,
                            'delta'      => round($newQty - $oldQty, 2),
                            'unit_price' => $unitPrice,
                        ];
                    }
                }
            }

            SaleItem::whereKey($existing->id)->update($updates);
        }

        unset($data['saleItems']);

        // Capture the submitted payment-section fields so afterSave() can
        // reconcile them onto the sale's linked Payment record. These aren't
        // Sale columns, so if we don't capture them here Filament's default
        // $record->update($data) would just silently ignore them.
        $this->pendingPaymentDetails = [
            'payment_method'     => $data['payment_method'] ?? null,
            'reference'          => $data['reference'] ?? null,
            'cheque_number'      => $data['cheque_number'] ?? null,
            'bank_name'          => $data['bank_name'] ?? null,
            'cheque_due_date'    => $data['cheque_due_date'] ?? null,
            'transfer_reference' => $data['transfer_reference'] ?? null,
            'transfer_date'      => $data['transfer_date'] ?? null,
        ];

        return $data;
    }

    protected function afterSave(): void
    {
        $sale = $this->getRecord();

        // Create any new items that were added in this edit session
        if (! empty($this->pendingNewItems)) {
            DB::transaction(function () use ($sale): void {
                foreach ($this->pendingNewItems as $item) {
                    $quantity   = ! empty($item['motorcycle_unit_id']) ? 1.0 : (float) ($item['quantity'] ?? 1);
                    $unitPrice  = (float) ($item['unit_price'] ?? 0);
                    $lineTotal  = round($quantity * $unitPrice, 2);
                    $lineTax    = round($lineTotal * (20 / 120), 2);

                    $saleItem = SaleItem::create([
                        'sale_id'                 => $sale->id,
                        'product_id'              => $item['product_id'] ?? null,
                        'motorcycle_unit_id'      => $item['motorcycle_unit_id'] ?? null,
                        'quantity'                => $quantity,
                        'unit_price'              => $unitPrice,
                        'discount'                => (float) ($item['discount'] ?? 0),
                        'tax'                     => $lineTax,
                        'total'                   => $lineTotal,
                        'warranty_duration_value' => $item['warranty_duration_value'] ?? null,
                        'warranty_duration_unit'  => $item['warranty_duration_unit'] ?? null,
                        'warranty_kilometers'     => filled($item['warranty_kilometers'] ?? null) ? (int) $item['warranty_kilometers'] : null,
                    ]);

                    // Stock exit movement
                    $warehouseId = ! empty($item['motorcycle_unit_id'])
                        ? MotorcycleUnit::withoutGlobalScopes()->find((int) $item['motorcycle_unit_id'])?->warehouse_id
                        : $this->resolveProductWarehouse((int) ($item['product_id'] ?? 0));

                    StockService::movement([
                        'company_id'         => $sale->company_id,
                        'product_id'         => $item['product_id'] ?? null,
                        'motorcycle_unit_id' => $item['motorcycle_unit_id'] ?? null,
                        'warehouse_id'       => $warehouseId,
                        'movement_type'      => 'sale',
                        'type'               => 'exit',
                        'quantity'           => $quantity,
                        'unit_cost'          => $unitPrice,
                        'reference'          => $sale->sale_number,
                        'reference_type'     => Sale::class,
                        'reference_id'       => $sale->id,
                        'notes'              => 'Sale #' . $sale->sale_number,
                        'user_id'            => auth()->id(),
                    ]);

                    // Update motorcycle unit status if applicable
                    if (! empty($item['motorcycle_unit_id'])) {
                        $unit = MotorcycleUnit::find((int) $item['motorcycle_unit_id']);
                        if ($unit) {
                            $unit->update(['status' => 'sold']);
                        }
                    }
                }
            });

            $this->pendingNewItems = [];
        }

        // Apply stock deltas for any quantity changes made to existing product
        // lines (the SaleItem rows were already re-totalled in mutateFormDataBeforeSave).
        if (! empty($this->pendingQtyAdjustments)) {
            foreach ($this->pendingQtyAdjustments as $adjustment) {
                $this->applyQuantityStockDelta($sale, $adjustment);
            }

            $this->pendingQtyAdjustments = [];
        }

        // Always recalculate the sale's aggregate totals from the CURRENT sale
        // items. This must run on EVERY save — not only when a brand-new item was
        // added — so that edits to an existing line's discount (Remise), warranty,
        // etc. are actually rolled up onto the sale itself. Without this the sale
        // (and any document that falls back to sale->total) keeps its initial
        // amount even though sale_items.discount was updated.
        $this->recalculateSaleTotals($sale);

        // Reconcile the payment-section fields onto the sale's linked payment.
        if ($this->pendingPaymentDetails !== null) {
            $this->syncPaymentDetailsToLatestPayment($sale, $this->pendingPaymentDetails);
            $this->pendingPaymentDetails = null;
        }

        // Propagate the sale_date to all linked documents
        Document::query()
            ->where('sale_id', $sale->id)
            ->update(['document_date' => $sale->sale_date]);

        // Re-sync warranties from updated SaleItem warranty fields
        WarrantyService::activateFromSale($sale);

        // Sync warranty document metadata
        $warrantyType = DocumentType::query()
            ->where('code', DocumentType::WARRANTY_CONTRACT)
            ->first();

        if (! $warrantyType) {
            return;
        }

        $sale->loadMissing('saleItems.product');

        Document::query()
            ->where('sale_id', $sale->id)
            ->where('document_type_id', $warrantyType->id)
            ->each(function (Document $document) use ($sale): void {
                $warrantySaleItem = $sale->saleItems->first(function (SaleItem $item): bool {
                    if ($item->motorcycle_unit_id) {
                        return true;
                    }
                    return in_array($item->product?->type, ['trotinette', 'velo_electrique', 'velo_normal'], true)
                        || (bool) $item->product?->has_warranty;
                });

                if (! $warrantySaleItem) {
                    return;
                }

                $metadata = $document->metadata ?? [];
                $metadata['warranty_duration_value'] = $warrantySaleItem->warranty_duration_value;
                $metadata['warranty_duration_unit']  = $warrantySaleItem->warranty_duration_unit;
                $metadata['warranty_kilometers']     = $warrantySaleItem->warranty_kilometers;

                $document->update(['metadata' => $metadata]);
            });
    }

    /**
     * Re-roll the sale's aggregate money columns from its current line items.
     *
     * Gross = Σ(item.total) (TTC per line), discount = Σ(item.discount) clamped to
     * gross so the net can never go negative. Net is TTC; tax/subtotal are reverse
     * extracted at 20%. remaining_amount / payment_status are recomputed against the
     * already-persisted paid_amount. Called on every edit so a changed Remise (even
     * 0) is reflected on the sale — and therefore on any regenerated document.
     */
    private function recalculateSaleTotals(Sale $sale): void
    {
        $allItems      = $sale->saleItems()->get();
        $grossTotal    = $allItems->sum(fn (SaleItem $i) => (float) $i->total);
        $totalDiscount = min($allItems->sum(fn (SaleItem $i) => (float) $i->discount), $grossTotal);

        // A bundled repair ticket's cost is added on top of the sale lines (it is
        // NOT stored as a sale_item), exactly as SaleService::create() does. Keep
        // it in the net so editing such a sale never drops the repair amount.
        $repairTotal   = $sale->repair_ticket_id
            ? max(0.0, (float) ($sale->repairTicket?->total_cost ?? 0))
            : 0.0;

        $netTotal      = round(max(0.0, $grossTotal - $totalDiscount) + $repairTotal, 2);
        $saleTax       = round($netTotal * (20 / 120), 2);
        $saleSubtotal  = round($netTotal - $saleTax, 2);
        $paid          = (float) $sale->paid_amount;
        $remaining     = max(0.0, round($netTotal - $paid, 2));

        $sale->update([
            'subtotal'         => $saleSubtotal,
            'tax'              => $saleTax,
            'discount'         => round($totalDiscount, 2),
            'total'            => $netTotal,
            'remaining_amount' => $remaining,
            'payment_status'   => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
        ]);
    }

    /**
     * Reconcile the sale form's payment-section fields onto the sale's most
     * recent Payment record, so editing the cheque number / bank / due date (or
     * switching the method) on the sale actually corrects the linked payment
     * instead of being silently discarded.
     *
     * Scope is deliberately narrow: this NEVER touches amount or status — those
     * stay under PaymentService's control (they drive the ledger/treasury and
     * motorcycle-unit-hold logic). It only corrects identifying details:
     *   - Same method as the existing payment → fix cheque/bank/reference typos.
     *   - Different method → reclassify the payment + swap its sub-record.
     * Use the "Add payment" action for a genuinely new/additional payment.
     */
    private function syncPaymentDetailsToLatestPayment(Sale $sale, array $submitted): void
    {
        $method = $submitted['payment_method'] ?? null;

        if (! $method) {
            return;
        }

        $payment = $sale->payments()->with(['chequePayment', 'bankTransferPayment'])->latest('id')->first();

        if (! $payment) {
            // No payment recorded yet for this sale — nothing to reconcile against.
            // Use "Add payment" to record the first one.
            return;
        }

        if ($payment->payment_method === $method) {
            if ($method === 'card' && filled($submitted['reference'])) {
                $payment->update(['reference' => $submitted['reference']]);
            }

            if ($method === 'cheque' && $payment->chequePayment) {
                $payment->chequePayment->update([
                    'cheque_number' => $submitted['cheque_number'] ?? $payment->chequePayment->cheque_number,
                    'bank_name'     => $submitted['bank_name'] ?? $payment->chequePayment->bank_name,
                    'due_date'      => $submitted['cheque_due_date'] ?? $payment->chequePayment->due_date,
                ]);

                if (filled($submitted['cheque_number'])) {
                    $payment->update(['reference' => $submitted['cheque_number']]);
                }
            }

            if ($method === 'bank_transfer' && $payment->bankTransferPayment) {
                $payment->bankTransferPayment->update([
                    'bank_name'        => $submitted['bank_name'] ?? $payment->bankTransferPayment->bank_name,
                    'reference_number' => $submitted['transfer_reference'] ?? $payment->bankTransferPayment->reference_number,
                    'transfer_date'    => $submitted['transfer_date'] ?? $payment->bankTransferPayment->transfer_date,
                ]);

                if (filled($submitted['transfer_reference'])) {
                    $payment->update(['reference' => $submitted['transfer_reference']]);
                }
            }

            return;
        }

        // Method reclassified (e.g. cash -> cheque). Swap the sub-record type;
        // amount/status are left untouched.
        $payment->chequePayment?->delete();
        $payment->bankTransferPayment?->delete();

        $payment->update([
            'payment_method' => $method,
            'reference'      => match ($method) {
                'cheque'        => $submitted['cheque_number'] ?? null,
                'bank_transfer' => $submitted['transfer_reference'] ?? null,
                default         => $submitted['reference'] ?? null,
            },
        ]);

        if ($method === 'cheque' && filled($submitted['cheque_number'])) {
            ChequePayment::create([
                'payment_id'    => $payment->id,
                'cheque_number' => $submitted['cheque_number'],
                'bank_name'     => $submitted['bank_name'] ?? null,
                'due_date'      => $submitted['cheque_due_date'] ?? null,
                'status'        => 'received',
            ]);
        }

        if ($method === 'bank_transfer' && filled($submitted['bank_name'])) {
            BankTransferPayment::create([
                'payment_id'       => $payment->id,
                'bank_name'        => $submitted['bank_name'],
                'reference_number' => $submitted['transfer_reference'] ?? null,
                'transfer_date'    => $submitted['transfer_date'] ?? now()->toDateString(),
                'status'           => 'sent',
            ]);
        }
    }

    private static function bankOptions(): array
    {
        return [
            'Attijariwafa Bank'               => 'Attijariwafa Bank',
            'Banque Centrale Populaire (BCP)' => 'Banque Centrale Populaire (BCP)',
            'Bank of Africa (BOA)'            => 'Bank of Africa (BOA)',
            'CIH Bank'                        => 'CIH Bank',
            'Al Barid Bank'                   => 'Al Barid Bank',
            'Crédit Agricole du Maroc'        => 'Crédit Agricole du Maroc',
            'Crédit du Maroc'                 => 'Crédit du Maroc',
            'BMCI'                            => 'BMCI',
            'CFG Bank'                        => 'CFG Bank',
            'Saham Bank'                      => 'Saham Bank',
            'Umnia Bank'                      => 'Umnia Bank',
            'Bank Assafa'                     => 'Bank Assafa',
            'Bank Al Yousr'                   => 'Bank Al Yousr',
            'Al Akhdar Bank'                  => 'Al Akhdar Bank',
            'Bank Al-Tamweel wal-Inma'        => 'Bank Al-Tamweel wal-Inma',
        ];
    }

    /**
     * Record a compensating stock movement for a quantity change on an existing
     * product line. A quantity increase leaves more stock (exit / sale); a decrease
     * returns stock (entry / return, restored at cost 0). Best-effort: a missing
     * warehouse or a movement failure must never break the sale save.
     */
    private function applyQuantityStockDelta(Sale $sale, array $adjustment): void
    {
        $productId = (int) ($adjustment['product_id'] ?? 0);
        $delta     = (float) ($adjustment['delta'] ?? 0);

        if ($productId <= 0 || abs($delta) < 0.0001) {
            return;
        }

        $warehouseId = $this->resolveProductWarehouse($productId);

        if (! $warehouseId) {
            return;
        }

        try {
            StockService::movement([
                'company_id'     => $sale->company_id,
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId,
                'movement_type'  => $delta > 0 ? 'sale' : 'return',
                'type'           => $delta > 0 ? 'exit' : 'entry',
                'quantity'       => abs($delta),
                'unit_cost'      => $delta > 0 ? (float) ($adjustment['unit_price'] ?? 0) : 0.0,
                'reference'      => $sale->sale_number,
                'reference_type' => Sale::class,
                'reference_id'   => $sale->id,
                'notes'          => 'Sale #' . $sale->sale_number . ' quantity ' . ($delta > 0 ? 'increased' : 'reduced') . ' on edit',
                'user_id'        => auth()->id(),
            ]);
        } catch (\Throwable) {
            // Stock movement is best-effort; never break the sale save over it.
        }
    }

    private function resolveProductWarehouse(int $productId): ?int
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
}
