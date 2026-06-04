<!DOCTYPE html>
<html lang="{{ $document->language }}">

<head>

    <meta charset="UTF-8">

    <title>
        {{ $document->document_number }}
    </title>

    @include(
        'documents.partials.pdf-styles'
    )

</head>

<body class="{{ $template->rtl ? 'rtl' : 'ltr' }}">

    @if($template->watermark)

        <div class="watermark">

            {{ $template->watermark }}

        </div>

    @endif

    @if($template->header_enabled)

        <div class="header">

            @if($document->brand?->pdf_header)

                <img
                    src="{{ public_path('storage/' . $document->brand->pdf_header) }}"
                    width="100%"
                >

            @endif

        </div>

    @endif

    @if($document->brand?->qr_code)

        <div class="qr-code">

            <img
                src="{{ public_path('storage/' . $document->brand->qr_code) }}"
                width="90"
            >

        </div>

    @endif

    @yield('content')

    @if($template->footer_enabled)

        <div class="footer">

            {!! \App\Helpers\HtmlSanitizer::clean($document->company?->invoice_footer) !!}

        </div>

    @endif

</body>

</html>
