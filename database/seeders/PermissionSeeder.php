<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | RESET CACHE
        |--------------------------------------------------------------------------
        */

        app()[

            \Spatie\Permission\PermissionRegistrar::class

        ]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | PERMISSIONS
        |--------------------------------------------------------------------------
        */

        $permissions = [

            /*
            |--------------------------------------------------------------------------
            | SALES
            |--------------------------------------------------------------------------
            */

            'manage_sales',
            'create_sales',
            'edit_sales',
            'delete_sales',
            'view_sales',

            /*
            |--------------------------------------------------------------------------
            | USERS
            |--------------------------------------------------------------------------
            */

            'manage_users',
            'manage_roles',
            'manage_permissions',

            /*
            |--------------------------------------------------------------------------
            | STOCK
            |--------------------------------------------------------------------------
            */

            'manage_stock',

            'manage_products',

            'manage_warehouses',

            'manage_stock_transfers',

            'manage_purchases',

            'manage_motorcycles',

            /*
            |--------------------------------------------------------------------------
            | LOCAL WAREHOUSE STOCK
            |--------------------------------------------------------------------------
            */

            'manage_local_stock',

            'create_stock_entries',

            'create_stock_exits',

            'transfer_stock',

            'view_local_reports',

            /*
            |--------------------------------------------------------------------------
            | REPAIRS
            |--------------------------------------------------------------------------
            */

            'manage_repairs',

            'manage_technicians',

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTS
            |--------------------------------------------------------------------------
            */

            'manage_documents',

            /*
            |--------------------------------------------------------------------------
            | WARRANTY
            |--------------------------------------------------------------------------
            */

            'manage_warranty',

            'manage_reimbursements',

            /*
            |--------------------------------------------------------------------------
            | ACCOUNTING
            |--------------------------------------------------------------------------
            */

            'manage_transactions',

            'manage_payments',

            'manage_funds',

            /*
            |--------------------------------------------------------------------------
            | EXPENSES
            |--------------------------------------------------------------------------
            */

            'manage_expenses',

            /*
            |--------------------------------------------------------------------------
            | CLIENTS
            |--------------------------------------------------------------------------
            */

            'manage_clients',

            /*
            |--------------------------------------------------------------------------
            | SUPPLIERS
            |--------------------------------------------------------------------------
            */

            'manage_suppliers',

            /*
            |--------------------------------------------------------------------------
            | RESELLERS
            |--------------------------------------------------------------------------
            */

            'manage_resellers',

            'manage_reseller_debt',

            'block_reseller',

            /*
            |--------------------------------------------------------------------------
            | REPORTS
            |--------------------------------------------------------------------------
            */

            'view_reports',

            /*
            |--------------------------------------------------------------------------
            | SETTINGS
            |--------------------------------------------------------------------------
            */

            'manage_settings',

        ];

        foreach ($permissions as $permission) {

            Permission::firstOrCreate([

                'name' => $permission,

                'guard_name' => 'web',

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | ROLES
        |--------------------------------------------------------------------------
        */

        $roles = [

            'Super Admin',
            'Admin',
            'Manager',
            'Accountant',
            'Stock Manager',
            'Commercial',
            'Workshop',
            'Cashier',
            'Limited User',

        ];

        foreach ($roles as $roleName) {

            $role = Role::firstOrCreate([

                'name' => $roleName,

                'guard_name' => 'web',

            ]);

            if ($roleName === 'Super Admin') {

                $role->syncPermissions(

                    Permission::all()

                );

            } elseif ($roleName === 'Admin') {

                $role->syncPermissions([

                    'manage_sales',
                    'create_sales',
                    'edit_sales',
                    'delete_sales',
                    'view_sales',

                    'manage_users',
                    'manage_roles',
                    'manage_permissions',

                    'manage_stock',
                    'manage_products',
                    'manage_warehouses',
                    'manage_stock_transfers',
                    'manage_purchases',
                    'manage_motorcycles',

                    'manage_local_stock',
                    'create_stock_entries',
                    'create_stock_exits',
                    'transfer_stock',
                    'view_local_reports',

                    'manage_repairs',
                    'manage_technicians',

                    'manage_documents',

                    'manage_warranty',
                    'manage_reimbursements',

                    'manage_transactions',
                    'manage_payments',
                    'manage_funds',

                    'manage_expenses',

                    'manage_clients',

                    'manage_suppliers',

                    'manage_resellers',

                    'manage_reseller_debt',

                    'block_reseller',

                    'view_reports',

                    'manage_settings',

                ]);

            } elseif ($roleName === 'Manager') {

                $role->syncPermissions([

                    'manage_sales',

                    'view_sales',

                    'manage_stock',
                    'manage_warehouses',
                    'manage_stock_transfers',
                    'manage_purchases',
                    'manage_motorcycles',

                    'manage_local_stock',

                    'create_stock_entries',

                    'create_stock_exits',

                    'transfer_stock',

                    'view_local_reports',

                    'manage_repairs',
                    'manage_technicians',
                    'manage_reimbursements',

                    'manage_clients',

                    'manage_resellers',

                    'view_reports',

                ]);

            } elseif ($roleName === 'Accountant') {

                $role->syncPermissions([

                    'manage_transactions',
                    'manage_payments',
                    'manage_funds',

                    'manage_expenses',

                    'manage_documents',

                    'view_reports',

                ]);

            } elseif ($roleName === 'Stock Manager') {

                $role->syncPermissions([

                    'manage_stock',

                    'manage_products',
                    'manage_warehouses',
                    'manage_stock_transfers',
                    'manage_purchases',
                    'manage_motorcycles',

                    'manage_local_stock',

                    'create_stock_entries',

                    'create_stock_exits',

                    'transfer_stock',

                    'view_local_reports',

                ]);

            } elseif ($roleName === 'Commercial') {

                $role->syncPermissions([

                    'manage_sales',

                    'create_sales',

                    'edit_sales',

                    'view_sales',

                    'manage_clients',

                    'manage_resellers',

                ]);

            } elseif ($roleName === 'Workshop') {

                $role->syncPermissions([

                    'manage_repairs',
                    'manage_technicians',

                    'manage_warranty',
                    'manage_reimbursements',

                ]);

            } elseif ($roleName === 'Cashier') {

                $role->syncPermissions([

                    'view_sales',

                    'manage_transactions',
                    'manage_payments',
                    'manage_funds',

                ]);

            } else {

                $role->syncPermissions([]);

            }

        }
    }
}
