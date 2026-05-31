<?php

namespace App\Filament\Resources\Resellers\Pages;

use App\Filament\Resources\Resellers\ResellerResource;
use App\Models\Reseller;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateReseller extends CreateRecord
{
    protected static string $resource = ResellerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->isDuplicateReseller($data)) {
            Notification::make()
                ->danger()
                ->title(__('messages.duplicate_reseller'))
                ->send();

            throw new Halt();
        }

        return $data;
    }

    private function isDuplicateReseller(array $data, ?int $ignoreId = null): bool
    {
        $n = fn ($v) => blank($v) ? null : $v;

        $query = Reseller::withoutGlobalScopes()
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
