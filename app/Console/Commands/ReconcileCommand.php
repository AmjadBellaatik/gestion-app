<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Reconciliation\ReconciliationService;
use Illuminate\Console\Command;

class ReconcileCommand extends Command
{
    protected $signature = 'erp:reconcile
                            {--company= : Reconcile only this company ID (omit for all active companies)}';

    protected $description = 'Auto-heal missing warranties, stock movements, transactions, and sale totals';

    public function handle(ReconciliationService $service): int
    {
        $companyId = $this->option('company');

        $companies = $companyId
            ? Company::withoutGlobalScopes()->where('id', $companyId)->get()
            : Company::withoutGlobalScopes()->where('is_active', true)->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies found to reconcile.');
            return self::SUCCESS;
        }

        foreach ($companies as $company) {
            $this->line('');
            $this->info("▶  {$company->name} (ID {$company->id})");

            $stats = $service->reconcileCompany($company->id);

            $this->table(
                ['Reconciler', 'Records fixed'],
                [
                    ['Sale totals',            $stats['sale_totals']],
                    ['Payment status',         $stats['payment_status']],
                    ['Warranties',             $stats['warranties']],
                    ['Stock movements',        $stats['stock_movements']],
                    ['Payment transactions',   $stats['transactions']],
                ]
            );
        }

        $this->line('');
        $this->info('Reconciliation complete.');

        return self::SUCCESS;
    }
}
