<?php

namespace App\Services\Installer;

use App\Models\Company;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RepairTypeSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The single, dependency-correct, idempotent replacement for the fragile
 * PermissionSeeder → RoleSeeder → RolePermissionSeeder → … chain.
 *
 * Why not just `php artisan db:seed`?
 *   - DatabaseSeeder calls RolePermissionSeeder, which syncs a
 *     `manage_purchases` permission it never creates → Spatie throws
 *     PermissionDoesNotExist and the whole install aborts.
 *   - DocumentTypeSeeder / RepairTypeSeeder early-return unless a Company
 *     already exists, but DatabaseSeeder seeds them *before* creating the
 *     company, so on a fresh DB no document types are ever written.
 *   - DatabaseSeeder also creates a hard-coded "Default Company" + admin.
 *
 * This class fixes the ordering, removes the phantom permission, and is
 * safe to re-run after a partial failure (every write is firstOrCreate /
 * updateOrCreate keyed on natural keys).
 */
class BootstrapSeeder
{
    /** Canonical permission set — the proven production list (34 entries). */
    public const PERMISSIONS = [
        // Sales
        'manage_sales', 'create_sales', 'edit_sales', 'delete_sales', 'view_sales',
        // Users / RBAC
        'manage_users', 'manage_roles', 'manage_permissions',
        // Stock
        'manage_stock', 'manage_products', 'manage_warehouses', 'manage_stock_transfers', 'manage_motorcycles',
        // Local warehouse stock
        'manage_local_stock', 'create_stock_entries', 'create_stock_exits', 'transfer_stock', 'view_local_reports',
        // Repairs
        'manage_repairs', 'manage_technicians',
        // Documents
        'manage_documents',
        // Warranty
        'manage_warranty', 'manage_reimbursements',
        // Accounting
        'manage_transactions', 'manage_payments', 'manage_funds',
        // Expenses
        'manage_expenses',
        // Clients / suppliers / resellers
        'manage_clients', 'manage_suppliers', 'manage_resellers', 'manage_reseller_debt', 'block_reseller',
        // Reports / settings
        'view_reports', 'manage_settings',
    ];

    public const ROLES = [
        'Super Admin', 'Admin', 'Manager', 'Accountant',
        'Stock Manager', 'Commercial', 'Workshop', 'Cashier', 'Limited User',
    ];

    public const SUPER_ADMIN_ROLE = 'Super Admin';

    private const GUARD = 'web';

    /**
     * Non-company-scoped bootstrap data: permissions, roles, the
     * role → permission map. Run once, right after migrations.
     */
    public function seedPlatform(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () {
            foreach (self::PERMISSIONS as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
            }

            foreach (self::ROLES as $name) {
                Role::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
            }

            foreach ($this->rolePermissionMap() as $roleName => $permissions) {
                $role = Role::where(['name' => $roleName, 'guard_name' => self::GUARD])->first();
                if (! $role) {
                    continue;
                }

                $permissions === '*'
                    ? $role->syncPermissions(Permission::where('guard_name', self::GUARD)->get())
                    : $role->syncPermissions($permissions);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Company-scoped reference data: document types + PDF templates, repair
     * document types, and the default settings rows. Runs after the first
     * company exists. Idempotent (updateOrCreate / firstOrCreate).
     */
    public function seedCompanyReferenceData(Company $company): void
    {
        // The existing seeders resolve the company via Company::first() /
        // Company::all() and SettingService via session('company_id').
        $previousCompanyId = session('company_id');
        session(['company_id' => $company->id]);

        try {
            (new DocumentTypeSeeder)->run();   // 7 rich commercial/legal types + FR templates
            (new RepairTypeSeeder)->run();     // short-code workshop document types
            (new SettingSeeder)->run();        // default company / pdf / numbering / workshop settings
        } finally {
            $previousCompanyId === null
                ? session()->forget('company_id')
                : session(['company_id' => $previousCompanyId]);
        }
    }

    /** @return array<string,string|array<int,string>> */
    private function rolePermissionMap(): array
    {
        return [
            'Super Admin' => '*',

            'Admin' => [
                'manage_sales', 'create_sales', 'edit_sales', 'delete_sales', 'view_sales',
                'manage_users', 'manage_roles', 'manage_permissions',
                'manage_stock', 'manage_products', 'manage_warehouses', 'manage_stock_transfers', 'manage_motorcycles',
                'manage_local_stock', 'create_stock_entries', 'create_stock_exits', 'transfer_stock', 'view_local_reports',
                'manage_repairs', 'manage_technicians',
                'manage_documents', 'manage_warranty', 'manage_reimbursements',
                'manage_transactions', 'manage_payments', 'manage_funds',
                'manage_expenses', 'manage_clients', 'manage_suppliers',
                'manage_resellers', 'manage_reseller_debt', 'block_reseller',
                'view_reports', 'manage_settings',
            ],

            'Manager' => [
                'manage_sales', 'view_sales',
                'manage_stock', 'manage_warehouses', 'manage_stock_transfers', 'manage_motorcycles',
                'manage_local_stock', 'create_stock_entries', 'create_stock_exits', 'transfer_stock', 'view_local_reports',
                'manage_repairs', 'manage_technicians', 'manage_reimbursements',
                'manage_clients', 'manage_resellers',
                'view_reports',
            ],

            'Accountant' => [
                'manage_transactions', 'manage_payments', 'manage_funds',
                'manage_expenses', 'manage_documents', 'view_reports',
            ],

            'Stock Manager' => [
                'manage_stock', 'manage_products', 'manage_warehouses', 'manage_stock_transfers', 'manage_motorcycles',
                'manage_local_stock', 'create_stock_entries', 'create_stock_exits', 'transfer_stock', 'view_local_reports',
            ],

            'Commercial' => [
                'manage_sales', 'create_sales', 'edit_sales', 'view_sales',
                'manage_clients', 'manage_resellers',
            ],

            'Workshop' => [
                'manage_repairs', 'manage_technicians', 'manage_warranty', 'manage_reimbursements',
            ],

            'Cashier' => [
                'view_sales', 'manage_transactions', 'manage_payments', 'manage_funds',
            ],

            'Limited User' => [],
        ];
    }
}
