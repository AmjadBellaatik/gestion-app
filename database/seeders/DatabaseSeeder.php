<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Company;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ROLES & PERMISSIONS
        |--------------------------------------------------------------------------
        */

        $this->call([

            PermissionSeeder::class,

            RoleSeeder::class,

            RolePermissionSeeder::class,

            RepairTypeSeeder::class,

            DocumentTypeSeeder::class,

            SettingSeeder::class,

        ]);

        /*
        |--------------------------------------------------------------------------
        | DEFAULT COMPANY
        |--------------------------------------------------------------------------
        */

        $company = Company::firstOrCreate(

            [
                'name' => 'Default Company',
            ],

            [

                'email' => 'company@test.com',

                'phone' => '0600000000',

                'city' => 'Fes',

                'country' => 'Morocco',

                'default_language' => 'fr',

                'tax_rate' => 20,

            ]

        );

        /*
        |--------------------------------------------------------------------------
        | DEFAULT ADMIN USER
        |--------------------------------------------------------------------------
        */

        $user = User::firstOrCreate(

            [
                'email' => 'admin@test.com',
            ],

            [

                'name' => 'Admin',

                'password' => bcrypt('password'),

                'language' => 'fr',

            ]

        );

        /*
        |--------------------------------------------------------------------------
        | LINK USER TO COMPANY
        |--------------------------------------------------------------------------
        */

        $user->companies()->syncWithoutDetaching([

            $company->id

        ]);

        /*
        |--------------------------------------------------------------------------
        | ASSIGN SUPER ADMIN ROLE
        |--------------------------------------------------------------------------
        */

        if (! $user->hasRole('Super Admin')) {

            $user->assignRole(
                'Super Admin'
            );

        }
    }
}