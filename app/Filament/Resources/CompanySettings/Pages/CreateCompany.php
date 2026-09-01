<?php

namespace App\Filament\Resources\CompanySettings\Pages;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use App\Models\Company;
use App\Services\Company\CompanyProvisioningService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Create Company — Super Admin only.
 *
 * Filament already blocks this page through CompanySettingResource::canCreate()
 * (→ CompanyPolicy::create → "Super Admin"). The explicit mount() check is a
 * second, independent gate so a forged/direct request to /admin/.../create
 * still 403s even if the resource guard were ever loosened.
 */
class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanySettingResource::class;

    public function mount(): void
    {
        abort_unless(
            (bool) auth()->user()?->can('create', Company::class),
            403
        );

        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Authorise once more at the write boundary.
        $this->authorizeCreation();

        return app(CompanyProvisioningService::class)->create($data, auth()->user());
    }

    protected function authorizeCreation(): void
    {
        abort_unless(
            (bool) auth()->user()?->can('create', Company::class),
            403
        );
    }

    protected function getRedirectUrl(): string
    {
        return CompanySettingResource::getUrl('edit', ['record' => $this->record]);
    }
}
