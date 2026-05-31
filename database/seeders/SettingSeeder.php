<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Services\Settings\SettingService;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | COMPANY SETTINGS
        |--------------------------------------------------------------------------
        */

        SettingService::set(

            'company',

            'default_language',

            'fr'

        );

        SettingService::set(

            'company',

            'tax_rate',

            '20'

        );

        /*
        |--------------------------------------------------------------------------
        | PDF SETTINGS
        |--------------------------------------------------------------------------
        */

        SettingService::set(

            'pdf',

            'paper_size',

            'A4'

        );

        SettingService::set(

            'pdf',

            'orientation',

            'portrait'

        );

        /*
        |--------------------------------------------------------------------------
        | NUMBERING
        |--------------------------------------------------------------------------
        */

        SettingService::set(

            'numbering',

            'invoice_prefix',

            'FAC'

        );

        SettingService::set(

            'numbering',

            'quote_prefix',

            'DEV'

        );

        /*
        |--------------------------------------------------------------------------
        | WORKSHOP
        |--------------------------------------------------------------------------
        */

        SettingService::set(

            'workshop',

            'default_repair_status',

            'open'

        );

        SettingService::set(

            'workshop',

            'enable_warranty',

            '1'

        );

        /*
        |--------------------------------------------------------------------------
        | REPAIR SETTINGS
        |--------------------------------------------------------------------------
        */

        SettingService::set(

            'repair',

            'default_labor_rate',

            '250'

        );

        /*
        |--------------------------------------------------------------------------
        | MAIL
        |--------------------------------------------------------------------------
        */

        SettingService::set('mail', 'mail_host', 'smtp.example.com');
        SettingService::set('mail', 'mail_port', '587');
        SettingService::set('mail', 'mail_username', '');
        SettingService::set('mail', 'mail_password', '');
        SettingService::set('mail', 'mail_encryption', 'tls');
        SettingService::set('mail', 'from_address', 'no-reply@example.com');
        SettingService::set('mail', 'from_name', config('app.name'));
    }
}
