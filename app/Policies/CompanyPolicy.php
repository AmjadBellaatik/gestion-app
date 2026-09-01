<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

/**
 * Authorisation for the Company entity.
 *
 * Only CREATE and DELETE are tightened here — both are reserved for the
 * "Super Admin" role. Viewing and editing keep the platform's existing
 * open behaviour so Admins (and anyone else who can already reach the
 * Company Settings screen) retain their edit rights, including editing
 * the visual identity.
 *
 * Note: AuthServiceProvider registers a Gate::before that already grants
 * every ability to Super Admin. These methods are still declared
 * explicitly so the rule is readable and enforced even if that global
 * short-circuit is ever removed.
 */
class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Company $company): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function update(User $user, Company $company): bool
    {
        return true;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return false;
    }
}
