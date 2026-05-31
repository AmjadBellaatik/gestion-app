<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateClient extends CreateRecord
{
    protected static string $resource =
        ClientResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {

        if ($this->isDuplicateClient($data)) {
            Notification::make()
                ->danger()
                ->title(__('messages.duplicate_client'))
                ->send();

            throw new Halt();
        }

        $data['company_id'] =
            session('company_id');

        /*
        |--------------------------------------------------------------------------
        | PERSON
        |--------------------------------------------------------------------------
        */

        if (
            $data['client_type'] === 'person'
        ) {

            $data['company_name'] = null;

            $data['administration_name'] = null;

            $data['ice'] = null;

            $data['rc'] = null;

            $data['if'] = null;

            $data['representative_name'] = null;

            $data['department'] = null;

            $data['responsible_person'] = null;
        }

        /*
        |--------------------------------------------------------------------------
        | COMPANY
        |--------------------------------------------------------------------------
        */

        if (
            $data['client_type'] === 'company'
        ) {

            $data['first_name'] = null;

            $data['last_name'] = null;

            $data['cin'] = null;

            $data['birth_date'] = null;

            $data['nationality'] = null;

            $data['administration_name'] = null;

            $data['department'] = null;

            $data['responsible_person'] = null;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATION
        |--------------------------------------------------------------------------
        */

        if (
            $data['client_type'] === 'administration'
        ) {

            $data['first_name'] = null;

            $data['last_name'] = null;

            $data['cin'] = null;

            $data['birth_date'] = null;

            $data['nationality'] = null;

            $data['company_name'] = null;

            $data['ice'] = null;

            $data['rc'] = null;

            $data['if'] = null;

            $data['representative_name'] = null;
        }

        return $data;
    }

    private function isDuplicateClient(array $data, ?int $ignoreId = null): bool
    {
        $type = $data['client_type'] ?? null;

        $query = Client::withoutGlobalScopes()
            ->where('company_id', session('company_id'))
            ->where('client_type', $type);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $n = fn ($v) => blank($v) ? null : $v;

        match ($type) {
            'person' => $query
                ->where('first_name', $n($data['first_name'] ?? null))
                ->where('last_name', $n($data['last_name'] ?? null))
                ->where('cin', $n($data['cin'] ?? null)),

            'company' => $query
                ->where('company_name', $n($data['company_name'] ?? null))
                ->where('ice', $n($data['ice'] ?? null)),

            'administration' => $query
                ->where('administration_name', $n($data['administration_name'] ?? null))
                ->where('phone', $n($data['phone'] ?? null)),

            default => null,
        };

        return $query->exists();
    }
}
