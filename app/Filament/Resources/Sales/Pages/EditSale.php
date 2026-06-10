<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    /** New items added in this edit session — processed in afterSave. */
    protected array $pendingNewItems = [];

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
                        ->default('cash'),
                    TextInput::make('reference')
                        ->label(__('messages.reference'))
                        ->placeholder(__('messages.optional')),
                    Textarea::make('notes')
                        ->label(__('messages.notes'))
                        ->rows(2)
                        ->placeholder(__('messages.optional')),
                ])
                ->action(function (array $data): void {
                    $sale = $this->getRecord();

                    Payment::create([
                        'sale_id'        => $sale->id,
                        'client_id'      => $sale->client_id,
                        'amount'         => (float) $data['amount'],
                        'payment_method' => $data['payment_method'],
                        'reference'      => filled($data['reference'] ?? null) ? $data['reference'] : null,
                        'notes'          => filled($data['notes'] ?? null) ? $data['notes'] : 'Payment for sale ' . $sale->sale_number,
                    ]);

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

        foreach ($data['saleItems'] ?? [] as $row) {
            $itemId = $row['_sale_item_id'] ?? null;

            if (! $itemId) {
                // New item added during edit — queue for creation in afterSave
                $this->pendingNewItems[] = $row;
                continue;
            }

            SaleItem::whereKey($itemId)
                ->where('sale_id', $this->getRecord()->id)
                ->update([
                    'discount'                => (float) ($row['discount'] ?? 0),
                    'warranty_duration_value' => $row['warranty_duration_value'] ?? null,
                    'warranty_duration_unit'  => $row['warranty_duration_unit'] ?? null,
                    'warranty_kilometers'     => filled($row['warranty_kilometers'] ?? null) ? (int) $row['warranty_kilometers'] : null,
                ]);
        }

        unset($data['saleItems']);

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

                // Recalculate sale totals from all items (existing + new)
                $sale->loadMissing('saleItems');
                $allItems     = $sale->saleItems()->get();
                $grossTotal   = $allItems->sum(fn ($i) => (float) $i->total);
                $totalDiscount = $allItems->sum(fn ($i) => (float) $i->discount);
                $netTotal     = max(0.0, $grossTotal - $totalDiscount);
                $saleTax      = round($netTotal * (20 / 120), 2);
                $saleSubtotal = round($netTotal - $saleTax, 2);
                $remaining    = max(0.0, round($netTotal - (float) $sale->paid_amount, 2));

                $sale->update([
                    'subtotal'         => $saleSubtotal,
                    'tax'              => $saleTax,
                    'discount'         => $totalDiscount,
                    'total'            => round($netTotal, 2),
                    'remaining_amount' => $remaining,
                    'payment_status'   => $remaining <= 0 ? 'paid' : ($sale->paid_amount > 0 ? 'partial' : 'unpaid'),
                ]);
            });

            $this->pendingNewItems = [];
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
