<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider
    as ServiceProvider;

use Illuminate\Support\Facades\Gate;

use App\Models\Company;
use App\Policies\CompanyPolicy;

class AuthServiceProvider
    extends ServiceProvider
{
    /**
     * Explicit policy map — the project ships a custom AuthServiceProvider,
     * so register model policies here rather than relying on discovery.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Company::class => CompanyPolicy::class,
    ];

    public function boot(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);

        Gate::before(function (
            $user,
            $ability
        ) {

            return $user->hasRole(
                'Super Admin'
            )

                ? true

                : null;
        });
    }
}