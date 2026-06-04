<?php

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Support\Arr;

class DocumentPlaceholderService
{
    public static function context(Document $document): array
    {
        $document->loadMissing([
            'company',
            'documentType',
            'documentTemplate',
            'client',
            'supplier',
            'reseller',
            'items.product',
            'items.motorcycleUnit.motorcycleModel.homologation',
        ]);

        $motorcycleUnit = $document->primaryMotorcycleUnit();
        $motorcycleModel = $motorcycleUnit?->motorcycleModel;

        return [
            'document' => [
                'number' => $document->document_number,
                'date' => $document->document_date?->format('d/m/Y'),
                'status' => $document->status,
                'subtotal' => number_format((float) $document->subtotal, 2, ',', ' '),
                'tax' => number_format((float) $document->tax_amount, 2, ',', ' '),
                'total' => number_format((float) $document->total_amount, 2, ',', ' '),
                'uuid' => $document->uuid,
                'verification_url' => $document->verification_url,
            ],
            'company' => [
                'name' => $document->company?->name,
                'legal_name' => $document->company?->legal_name,
                'ice' => $document->company?->ice,
                'if' => $document->company?->if,
                'rc' => $document->company?->rc,
                'patente' => $document->company?->patente,
                'address' => $document->company?->legal_address ?: $document->company?->address,
                'phone' => $document->company?->phone,
                'email' => $document->company?->email,
                'bank_name' => $document->company?->bank_name,
                'rib' => $document->company?->rib,
            ],
            'client' => [
                'name' => $document->client?->display_name,
                'address' => $document->client?->address,
                'phone' => $document->client?->phone,
                'email' => $document->client?->email,
                'cin' => $document->client?->cin,
                'ice' => $document->client?->ice,
                'rc' => $document->client?->rc,
                'if' => $document->client?->if,
            ],
            'supplier' => [
                'name' => $document->supplier?->name,
                'address' => $document->supplier?->address,
                'phone' => $document->supplier?->phone,
                'email' => $document->supplier?->email,
            ],
            'motorcycle' => [
                'chassis_number' => $motorcycleUnit?->chassis_number,
                'fabrication_number' => $motorcycleUnit?->fabrication_number,
                'model' => $motorcycleModel?->modele,
                'marque' => $motorcycleModel?->marque,
                'type' => $motorcycleModel?->type,
                'variante' => $motorcycleModel?->variante,
                'version' => $motorcycleModel?->version,
                'category' => $motorcycleModel?->categorie,
                'homologation_number' => $motorcycleModel?->titre_homologation
                    ?: $motorcycleModel?->homologation?->homologation_number,
                'manufacturer' => $motorcycleModel?->homologation?->manufacturer
                    ?: $motorcycleModel?->usine_fabrication,
                'country' => $motorcycleModel?->pays_origine,
            ],
        ];
    }

    public static function generate(Document $document): array
    {
        $flat = [];

        foreach (Arr::dot(self::context($document)) as $key => $value) {
            // Strip placeholder delimiters from values so a field containing
            // "{{ company.ice }}" cannot cascade into a second replacement pass.
            $safe = str_replace(['{{', '}}'], ['', ''], (string) $value);
            $flat['{{ ' . $key . ' }}'] = $safe;
        }

        return $flat;
    }

    public static function replace(string $content, Document $document): string
    {
        $map = self::generate($document);

        return str_replace(
            array_keys($map),
            array_values($map),
            $content
        );
    }
}
