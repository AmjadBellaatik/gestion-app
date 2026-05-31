<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Company;
use App\Models\DocumentType;

class RepairTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [

            [
                'code' => 'DEV',
                'name' => 'Devis',
                'prefix' => 'DEV',
            ],

            [
                'code' => 'FAC',
                'name' => 'Facture',
                'prefix' => 'FAC',
            ],

            [
                'code' => 'BL',
                'name' => 'Bon Livraison',
                'prefix' => 'BL',
            ],

            [
                'code' => 'BC',
                'name' => 'Bon Commande',
                'prefix' => 'BC',
            ],

            [
                'code' => 'BE',
                'name' => 'Bon Entrée',
                'prefix' => 'BE',
            ],

            [
                'code' => 'BS',
                'name' => 'Bon Sortie',
                'prefix' => 'BS',
            ],

            [
                'code' => 'GAR',
                'name' => 'Garantie',
                'prefix' => 'GAR',
            ],

            [
                'code' => 'CONF',
                'name' => 'Conformité',
                'prefix' => 'CONF',
            ],

        ];

        foreach (

            Company::all()

            as $company

        ) {

            foreach (

                $types

                as $type

            ) {

                DocumentType::firstOrCreate([

                    'company_id' =>
                        $company->id,

                    'code' =>
                        $type['code'],

                ], [

                    'name' =>
                        $type['name'],

                    'prefix' =>
                        $type['prefix'],

                    'next_number' => 1,

                    'language' => 'fr',

                    'is_active' => true,

                ]);

            }

        }
    }
}