<?php

namespace App\Services\Documents;

use App\Models\Document;
use Barryvdh\DomPDF\PDF;

class PdfService
{
    public static function generate(Document $document): PDF
    {
        return DocumentService::generatePdf($document);
    }
}
