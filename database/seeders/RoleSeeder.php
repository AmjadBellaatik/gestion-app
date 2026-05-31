<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;

class RoleSeeder
    extends Seeder
{
    public function run(): void
    {
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

        foreach (
            $roles
            as $role
        ) {

            Role::firstOrCreate([

                'name' =>
                    $role,

                'guard_name' =>
                    'web',

            ]);

        }
    }
}