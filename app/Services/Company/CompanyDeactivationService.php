<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Deletes" a company the only way that is safe in this ERP: it deactivates
 * it. A hard delete would cascade through `company_user`, and every
 * company-scoped table (sales, payments, transactions, documents,
 * accounting, stock, repairs, warranties, …) via their foreign keys —
 * destroying legally and accountingly significant history. So instead:
 *
 *   - `companies.is_active = false`   (hidden from the switcher, resource,
 *                                      middleware and the switch endpoint)
 *   - all `company_user` links removed (no dangling memberships)
 *   - the caller's session repaired if they were viewing this company
 *
 * All in one transaction. Two invariants are enforced first:
 *   1. at least one active company must remain;
 *   2. the acting user must keep at least one usable (active) company.
 *
 * The row and its data stay intact, so a deactivation is fully reversible.
 */
class CompanyDeactivationService
{
    public function deactivate(Company $company, User $actor): void
    {
        if (! $company->is_active) {
            return;
        }

        $otherUsableExists = Company::query()
            ->where('is_active', true)
            ->where('name', '!=', 'Default Company')
            ->whereKeyNot($company->getKey())
            ->exists();

        if (! $otherUsableExists) {
            throw ValidationException::withMessages([
                'company' => __('messages.cannot_delete_last_company'),
            ]);
        }

        $actorKeepsACompany = $actor->companies()
            ->where('companies.is_active', true)
            ->where('companies.id', '!=', $company->getKey())
            ->exists();

        if (! $actorKeepsACompany) {
            throw ValidationException::withMessages([
                'company' => __('messages.cannot_leave_user_without_company'),
            ]);
        }

        DB::transaction(function () use ($company): void {
            $company->users()->detach();

            $company->forceFill(['is_active' => false])->save();
        });

        $this->repairSession($company, $actor);
    }

    /**
     * If the acting user was currently working inside the company that was
     * just removed, point their session at another active company they
     * still belong to (or clear it so SetCompany re-resolves next request).
     */
    private function repairSession(Company $company, User $actor): void
    {
        if ((int) session('company_id') !== (int) $company->getKey()) {
            return;
        }

        $fallback = $actor->companies()
            ->where('companies.is_active', true)
            ->where('companies.name', '!=', 'Default Company')
            ->orderBy('companies.id')
            ->value('companies.id');

        $fallback
            ? session(['company_id' => $fallback])
            : session()->forget('company_id');
    }
}
