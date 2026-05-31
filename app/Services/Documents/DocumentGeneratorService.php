<?php

namespace App\Services\Documents;

use App\Models\Sale;
use App\Models\Document;

class DocumentGeneratorService
{
    public static function generateSaleDocuments(
        Sale $sale
    ): void {

        $documents = [

            'INVOICE',
            'DELIVERY_NOTE',
            'CONFORMITY',

        ];

        foreach (
            $documents
            as $code
        ) {

            $documentType =

                DocumentTypeService::getByCode(
                    $code
                );

            if (! $documentType) {
                continue;
            }

            Document::create([

                'company_id' =>
                    $sale->company_id,

                'document_type_id' =>
                    $documentType->id,

                'client_id' =>
                    $sale->client_id,

                'sale_id' =>
                    $sale->id,

                'document_number' =>

                    $documentType->prefix .
                    '-' .
                    now()->timestamp,

                'document_date' =>
                    now(),

                'subtotal' =>
                    $sale->subtotal,

                'tax' =>
                    $sale->tax,

                'total' =>
                    $sale->total,

                'status' =>
                    'generated',

                'notes' =>
                    'Auto generated document',

            ]);

        }

    }
}