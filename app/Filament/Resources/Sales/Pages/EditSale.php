<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\SaleItem;
use App\Services\Warranty\WarrantyService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        foreach ($data['saleItems'] ?? [] as $row) {
            $itemId = $row['_sale_item_id'] ?? null;
            if (! $itemId) {
                continue;
            }

            SaleItem::whereKey($itemId)
                ->where('sale_id', $this->getRecord()->id)
                ->update([
                    'discount'                => (float) ($row['discount'] ?? 0),
                    'warranty_duration_value' => $row['warranty_duration_value'] ?? null,
                    'warranty_duration_unit'  => $row['warranty_duration_unit'] ?? null,
                    'warranty_kilometers'     => filled($row['warranty_kilometers']) ? (int) $row['warranty_kilometers'] : null,
                ]);
        }

        unset($data['saleItems']);

        return $data;
    }

    protected function afterSave(): void
    {
        $sale = $this->getRecord();

        // Re-sync warranties from updated SaleItem warranty fields
        WarrantyService::activateFromSale($sale);

        // Sync warranty document metadata so PDFs reflect the updated values
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
}
