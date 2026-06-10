<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Scopes\CompanyScope;
use App\Services\Documents\DocumentVerificationPresenter;
use Illuminate\Support\Str;

class DocumentVerificationController extends Controller
{
    public function verify(string $uuid)
    {
        if (! Str::isUuid($uuid)) {
            return view('documents.verify.not-found', [
                'authentic' => false,
                'document'  => null,
                'v'         => null,
            ]);
        }

        $document = Document::withoutGlobalScope(CompanyScope::class)
            ->with([
                'company',
                'documentType',
                'client',
                'sale',
                'supplier',
                'repairTicket',
                'items.product',
                'items.motorcycleUnit.motorcycleModel.homologation',
            ])
            ->where('uuid', $uuid)
            ->first();

        if (! $document) {
            return view('documents.verify.not-found', [
                'authentic' => false,
                'document'  => null,
                'v'         => null,
            ]);
        }

        return view($this->resolveView($document->documentType?->code), [
            'authentic' => true,
            'document'  => $document,
            'v'         => DocumentVerificationPresenter::from($document),
        ]);
    }

    private function resolveView(?string $code): string
    {
        return match ($code) {
            'CONFORMITY'      => 'documents.verify.conformity',
            'INVOICE'         => 'documents.verify.invoice',
            'QUOTE'           => 'documents.verify.quotation',
            'WARRANTY_CONTRACT' => 'documents.verify.warranty',
            'DELIVERY_NOTE'   => 'documents.verify.delivery-note',
            'SALE_RETURN'     => 'documents.verify.sale-return',
            'PURCHASE_ORDER'  => 'documents.verify.supplier-order',
            'OWNERSHIP'       => 'documents.verify.ownership',
            default           => 'documents.verify.generic',
        };
    }
}
