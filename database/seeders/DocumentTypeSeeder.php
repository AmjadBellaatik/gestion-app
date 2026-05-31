<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if (! $company) {
            return;
        }

        $documents = [
            [
                'name' => 'Devis',
                'code' => DocumentType::QUOTATION,
                'prefix' => 'DEV',
                'category' => 'commercial',
                'blade_view' => 'documents.pdf.commercial-quotation',
                'affects_stock' => false,
                'affects_accounting' => false,
            ],
            [
                'name' => 'Facture',
                'code' => DocumentType::INVOICE,
                'prefix' => 'FAC',
                'category' => 'commercial',
                'blade_view' => 'documents.pdf.commercial-invoice',
                'affects_stock' => false,
                'affects_accounting' => false,
            ],
            [
                'name' => 'Bon de livraison',
                'code' => DocumentType::DELIVERY_NOTE,
                'prefix' => 'BL',
                'category' => 'commercial',
                'blade_view' => 'documents.pdf.delivery-note',
                'affects_stock' => false,
                'affects_accounting' => false,
            ],
            [
                'name' => 'Bon de commande fournisseur',
                'code' => DocumentType::SUPPLIER_ORDER,
                'prefix' => 'BC',
                'category' => 'purchase',
                'blade_view' => 'documents.pdf.supplier-order',
                'affects_stock' => false,
                'affects_accounting' => false,
            ],
            [
                'name' => 'Contrat de garantie',
                'code' => DocumentType::WARRANTY_CONTRACT,
                'prefix' => 'GAR',
                'category' => 'legal',
                'blade_view' => 'documents.pdf.warranty-contract',
                'affects_stock' => false,
                'affects_accounting' => false,
            ],
            [
                'name' => 'Certificat de conformite',
                'code' => DocumentType::CONFORMITY,
                'prefix' => 'CONF',
                'category' => 'legal',
                'blade_view' => 'documents.pdf.conformity-certificate',
                'affects_stock' => false,
                'affects_accounting' => false,
            ],
            [
                'name' => 'Bon de retour',
                'code' => DocumentType::SALE_RETURN,
                'prefix' => 'RET',
                'category' => 'commercial',
                'blade_view' => 'documents.pdf.sale-return',
                'affects_stock' => true,
                'affects_accounting' => false,
            ],
            [
                'name' => 'Facture réparation',
                'code' => DocumentType::REPAIR_INVOICE,
                'prefix' => 'FREP',
                'category' => 'repair',
                'blade_view' => 'documents.pdf.repair-invoice',
                'affects_stock' => false,
                'affects_accounting' => false,
            ],
        ];

        foreach ($documents as $document) {
            $type = DocumentType::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $document['code'],
                ],
                [
                    'name' => $document['name'],
                    'prefix' => $document['prefix'],
                    'category' => $document['category'],
                    'blade_view' => $document['blade_view'],
                    'automatic_variables' => [
                        'client.name',
                        'client.address',
                        'motorcycle.chassis_number',
                        'motorcycle.model',
                        'document.total',
                        'company.ice',
                    ],
                    'header_enabled' => true,
                    'footer_enabled' => true,
                    'affects_stock' => $document['affects_stock'],
                    'affects_accounting' => $document['affects_accounting'],
                    'default_language' => 'fr',
                    'language' => 'fr',
                    'is_active' => true,
                ]
            );

            DocumentTemplate::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'document_type_id' => $type->id,
                    'language' => 'fr',
                    'is_default' => true,
                ],
                [
                    'name' => $document['name'] . ' - FR',
                    'category' => $document['category'],
                    'blade_view' => $document['blade_view'],
                    'version' => 1,
                    'variables' => $type->automatic_variables,
                    'orientation' => 'portrait',
                    'paper_size' => 'A4',
                    'rtl' => false,
                    'footer_enabled' => true,
                    'header_enabled' => true,
                    'signature_enabled' => true,
                    'stamp_enabled' => true,
                    'template_type' => $document['category'],
                ]
            );
        }
    }
}
