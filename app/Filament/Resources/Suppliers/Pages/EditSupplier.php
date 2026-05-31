<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Supplier;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->isDuplicateSupplier($data, $this->record->id)) {
            Notification::make()
                ->danger()
                ->title(__('messages.duplicate_supplier'))
                ->send();

            throw new Halt();
        }

        return $data;
    }

    private function isDuplicateSupplier(array $data, ?int $ignoreId = null): bool
    {
        $n = fn ($v) => blank($v) ? null : $v;

        $query = Supplier::withoutGlobalScopes()
            ->where('company_id', session('company_id'))
            ->where('name', $n($data['name'] ?? null))
            ->where('phone', $n($data['phone'] ?? null))
            ->where('email', $n($data['email'] ?? null));

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
