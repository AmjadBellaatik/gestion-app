<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $missing = [
            'manage_warehouses',
            'manage_stock_transfers',
            'manage_motorcycles',
            'manage_technicians',
            'manage_reimbursements',
            'manage_payments',
            'manage_funds',
        ];

        foreach ($missing as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'Super Admin' => Permission::all()->pluck('name')->toArray(),

            'Admin' => [
                'manage_sales', 'create_sales', 'edit_sales', 'delete_sales', 'view_sales',
                'manage_users', 'manage_roles', 'manage_permissions',
                'manage_stock', 'manage_products', 'manage_warehouses',
                'manage_stock_transfers', 'manage_motorcycles',
                'manage_local_stock', 'create_stock_entries', 'create_stock_exits',
                'transfer_stock', 'view_local_reports',
                'manage_repairs', 'manage_technicians',
                'manage_documents',
                'manage_warranty', 'manage_reimbursements',
                'manage_transactions', 'manage_payments', 'manage_funds',
                'manage_expenses',
                'manage_clients',
                'manage_suppliers',
                'manage_resellers', 'manage_reseller_debt', 'block_reseller',
                'view_reports',
                'manage_settings',
            ],

            'Manager' => [
                'manage_sales', 'view_sales',
                'manage_stock', 'manage_warehouses', 'manage_stock_transfers',
                'manage_purchases', 'manage_motorcycles',
                'manage_local_stock', 'create_stock_entries', 'create_stock_exits',
                'transfer_stock', 'view_local_reports',
                'manage_repairs', 'manage_technicians', 'manage_reimbursements',
                'manage_clients',
                'manage_resellers',
                'view_reports',
            ],

            'Stock Manager' => [
                'manage_stock', 'manage_products', 'manage_warehouses',
                'manage_stock_transfers', 'manage_motorcycles',
                'manage_local_stock', 'create_stock_entries', 'create_stock_exits',
                'transfer_stock', 'view_local_reports',
            ],

            'Workshop' => [
                'manage_repairs', 'manage_technicians',
                'manage_warranty', 'manage_reimbursements',
            ],

            'Cashier' => [
                'view_sales',
                'manage_transactions', 'manage_payments', 'manage_funds',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                if ($roleName === 'Super Admin') {
                    $role->syncPermissions(Permission::all());
                } else {
                    $role->givePermissionTo(
                        array_filter($permissions, fn ($p) => Permission::where('name', $p)->exists())
                    );
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void {}
};
