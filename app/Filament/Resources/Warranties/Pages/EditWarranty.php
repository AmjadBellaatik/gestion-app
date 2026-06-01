<?php

namespace App\Filament\Resources\Warranties\Pages;

use App\Filament\Resources\Warranties\WarrantyResource;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\SaleItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarranty extends EditRecord
{
    protected static string $resource = WarrantyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => WarrantyResource::isAdminUser()),
        ];
    }

    protected function afterSave(): void
    {
        $warranty = $this->getRecord();

        if (! $warranty->sale_id) {
            return;
        }

        // Sync warranty_kilometers back to the matching SaleItem
        $saleItem = SaleItem::query()
            ->where('sale_id', $warranty->sale_id)
            ->when(
                $warranty->motorcycle_unit_id,
                fn ($q) => $q->where('motorcycle_unit_id', $warranty->motorcycle_unit_id),
                fn ($q) => $q->where('product_id', $warranty->product_id)
            )
            ->first();

        if ($saleItem) {
            $saleItem->update([
                'warranty_kilometers' => $warranty->warranty_kilometers,
            ]);
        }

        // Sync warranty document metadata so the PDF reflects the new values
        $warrantyType = DocumentType::query()
            ->where('code', DocumentType::WARRANTY_CONTRACT)
            ->first();

        if (! $warrantyType) {
            return;
        }

        Document::query()
            ->where('sale_id', $warranty->sale_id)
            ->where('document_type_id', $warrantyType->id)
            ->each(function (Document $document) use ($warranty, $saleItem): void {
                $metadata = $document->metadata ?? [];
                $metadata['warranty_kilometers'] = $warranty->warranty_kilometers;

                if ($saleItem) {
                    $metadata['warranty_duration_value'] = $saleItem->warranty_duration_value;
                    $metadata['warranty_duration_unit']  = $saleItem->warranty_duration_unit;
                }

                $document->update(['metadata' => $metadata]);
            });
    }
}
