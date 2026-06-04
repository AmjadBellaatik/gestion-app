<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the scope.
     */
    public function apply(
        Builder $builder,
        Model $model
    ): void {

        if (!session()->has('company_id')) {
            // Fail-closed: missing tenant context must never expose cross-tenant data.
            // Queue jobs and internal services that need unrestricted access must call
            // Model::withoutGlobalScopes() explicitly.
            $builder->whereRaw('0 = 1');
            return;
        }

        $builder->where(
            $model->getTable() . '.company_id',
            session('company_id')
        );
    }
}