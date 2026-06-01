<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
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
                'item_type'               => $itemType,
                'product_id'              => $item->product_id,
                'motorcycle_unit_id'      => $item->motorcycle_unit_id,
                'quantity'                => $item->quantity,
                'unit_price'              => $item->unit_price,
                'warranty_duration_value' => $item->warranty_duration_value,
                'warranty_duration_unit'  => $item->warranty_duration_unit ?? 'years',
                'warranty_kilometers'     => $item->warranty_kilometers,
            ];
        })->values()->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['saleItems']);

        return $data;
    }

    protected function afterSave(): void
    {
        WarrantyService::activateFromSale($this->getRecord());
    }
}
