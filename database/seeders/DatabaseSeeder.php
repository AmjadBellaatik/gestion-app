<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        // Use ADMIN_EMAIL / ADMIN_PASSWORD env vars if set; otherwise generate
        // a cryptographically random password and print it once to the console.
        // Never ships with a hardcoded default password.
        $adminEmail    = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD') ?: Str::password(20);
        $isNew         = ! User::where('email', $adminEmail)->exists();

        $user = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name'     => 'Admin',
                'password' => Hash::make($adminPassword),
                'language' => 'fr',
            ]
        );

        if ($isNew) {
            $this->command->info("─────────────────────────────────────────");
            $this->command->info(" Admin account created");
            $this->command->info(" Email   : {$adminEmail}");
            $this->command->info(" Password: {$adminPassword}");
            $this->command->info(" Save this password — it will not be shown again.");
            $this->command->info("─────────────────────────────────────────");
        }

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