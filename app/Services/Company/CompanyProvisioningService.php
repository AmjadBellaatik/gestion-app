<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use App\Services\Installer\BootstrapSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Creates an additional company after installation: one transaction that
 * persists the company, links the creating Super Admin through the
 * existing `company_user` pivot (with the Super Admin role_id), and seeds
 * the per-company default settings.
 *
 * Document / repair types are NOT re-seeded here: since
 * 2026_06_05 make_document_types_global they carry a global `unique(code)`
 * constraint and are shared across companies — the installer seeds them
 * once for the first company. Re-running those seeders for a second
 * company would collide. Only genuinely per-company data (the `settings`
 * rows, keyed by company_id) is seeded, and idempotently.
 */
class CompanyProvisioningService
{
    /**
     * @param  array<string,mixed>  $attributes  validated Company columns
     */
    public function create(array $attributes, User $creator): Company
    {
        // Drop null / empty-string fields so NOT-NULL columns that carry a
        // database default (tax_rate, currency, default_language, …) fall back
        // to that default instead of a rejected explicit NULL.
        $attributes = array_filter(
            $attributes,
            static fn ($value) => $value !== null && $value !== '',
        );

        return DB::transaction(function () use ($attributes, $creator): Company {
            $attributes['is_active'] = $attributes['is_active'] ?? true;

            $company = Company::create($attributes);

            $superAdminRoleId = Role::where([
                'name' => BootstrapSeeder::SUPER_ADMIN_ROLE,
                'guard_name' => 'web',
            ])->value('id');

            // Attach the creator without disturbing their other memberships,
            // preserving the per-company role_id pivot semantics.
            $creator->companies()->syncWithoutDetaching([
                $company->id => ['role_id' => $superAdminRoleId],
            ]);

            $this->seedCompanySettings($company);

            return $company->refresh();
        });
    }

    /**
     * SettingSeeder writes through SettingService, which scopes every row
     * to session('company_id'). Point it at the new company for the call,
     * then restore. updateOrCreate inside the service keeps it idempotent.
     */
    private function seedCompanySettings(Company $company): void
    {
        $previous = session('company_id');
        session(['company_id' => $company->id]);

        try {
            (new SettingSeeder)->run();
        } finally {
            $previous === null
                ? session()->forget('company_id')
                : session(['company_id' => $previous]);
        }
    }
}
