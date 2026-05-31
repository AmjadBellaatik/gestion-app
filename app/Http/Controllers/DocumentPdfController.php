<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Documents\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DocumentPdfController extends Controller
{
    public function preview(Document $document): Response
    {
        if (
            $document->documentType?->code === DocumentType::CONFORMITY
            || in_array($document->documentType?->code, [DocumentType::INVOICE, DocumentType::QUOTATION], true)
            || ! $document->pdf_path
            || ! Storage::disk('public')->exists($document->pdf_path)
        ) {
            app(DocumentService::class)->storePdf($document);
            $document->refresh();
        }

        return response(Storage::disk('public')->get($document->pdf_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->document_number . '.pdf"',
        ]);
    }

    public function download(Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (
            $document->documentType?->code === DocumentType::CONFORMITY
            || in_array($document->documentType?->code, [DocumentType::INVOICE, DocumentType::QUOTATION], true)
            || ! $document->pdf_path
            || ! Storage::disk('public')->exists($document->pdf_path)
        ) {
            app(DocumentService::class)->storePdf($document);
            $document->refresh();
        }

        return Storage::disk('public')->download($document->pdf_path, $document->document_number . '.pdf');
    }

    public function regenerate(Document $document): RedirectResponse
    {
        app(DocumentService::class)->storePdf($document);

        return back()->with('status', __('messages.pdf_regenerated'));
    }

    public function email(Document $document): RedirectResponse
    {
        return back()->with('status', __('messages.email_sending_not_configured'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        return redirect('/admin/documents')->with('status', __('messages.document_deleted'));
    }
}
