<?php

namespace App\Filament\Resources\Resellers\Pages;

use App\Filament\Resources\Resellers\ResellerResource;
use App\Models\Reseller;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditReseller extends EditRecord
{
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->isDuplicateReseller($data, $this->record->id)) {
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
