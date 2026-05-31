<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Scopes\CompanyScope;

class DocumentVerificationController extends Controller
{
    public function verify(string $uuid)
    {
        $document = Document::withoutGlobalScope(CompanyScope::class)
            ->with([
                'company',
                'documentType',
                'client',
                'items.product',
                'items.motorcycleUnit.motorcycleModel.homologation',
            ])
            ->where('uuid', $uuid)
            ->first();

        if (! $document) {
            return view('documents.verify', [
                'authentic' => false,
                'document' => null,
            ]);
        }

        return view('documents.verify', [
            'authentic' => true,
            'document' => $document,
        ]);
    }
}
