<?php

namespace App\Services\Documents\PDF;

use App\Models\Document;
use App\Models\DocumentTemplate;

use Barryvdh\DomPDF\Facade\Pdf;

class DocumentPdfService
{
    public static function render(
        Document $document,
        ?DocumentTemplate $template = null
    ) {

        if (! $template) {

            $template = DocumentTemplate::query()

                ->where(
                    'document_type_id',
                    $document->document_type_id
                )

                ->where(
                    'language',
                    $document->language
                )

                ->where(
                    'is_default',
                    true
                )

                ->first();
        }

        if (! $template) {

            abort(
                404,
                'Document template not found.'
            );
        }

        app()->setLocale(
            $document->language
        );

        $pdf = Pdf::loadView(

            $template->blade_view,

            [

                'document' => $document,

                'template' => $template,

            ]

        );

        $pdf->setPaper(

            $template->paper_size ?? 'A4',

            $template->orientation ?? 'portrait'

        );

        return $pdf;
    }

    public static function stream(
        Document $document,
        ?DocumentTemplate $template = null
    ) {

        return self::render(

            $document,

            $template

        )->stream(

            $document->document_number . '.pdf'

        );
    }

    public static function download(
        Document $document,
        ?DocumentTemplate $template = null
    ) {

        return self::render(

            $document,

            $template

        )->download(

            $document->document_number . '.pdf'

        );
    }
}