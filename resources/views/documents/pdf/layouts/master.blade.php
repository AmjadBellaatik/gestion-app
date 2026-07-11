<!doctype html>
<html lang="{{ $document->language ?? 'fr' }}" dir="{{ ($document->language ?? 'fr') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">

    {{-- BASE STYLES — shared by every document template.
         Child templates push additions via @push('styles').
         The safe-zone block below is immutable and always wins. --}}
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Watermark — position:fixed renders it on every page behind content */
        .pdf-watermark {
            position: fixed;
            top: 40%;
            left: 0;
            right: 0;
            z-index: -1;
            text-align: center;
            font-size: 54px;
            font-weight: 700;
            color: #f0f2f5;
        }

        /* Document header table */
        .doc-header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .doc-header-brand { display: block; width: 170px; text-align: center; }
        .company-logo     { display: block; margin: 0 auto; max-height: 56px; max-width: 140px; }
        .company-name     { display: block; margin-top: 5px; font-size: 15px; font-weight: 700;
                            text-transform: uppercase; letter-spacing: 0.03em; text-align: center; }
        .header-qr        { width: 56px; height: 56px; display: block; margin-left: auto; }
        .header-qr-label  { font-size: 7.5px; text-align: center; color: #6b7280; margin-top: 2px; }

        /* Document title and reference */
        .doc-title {
            margin: 14px 0 6px;
            text-align: center;
            font-size: 19px;
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .doc-ref { text-align: center; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 14px; }

        /* Info boxes */
        .info-table    { width: 100%; border-collapse: collapse; }
        .info-table td { vertical-align: top; padding-right: 10px; }
        .box           { border: 1px solid #6b7280; padding: 7px 10px; border-radius: 2px; }
        .box-title     { font-weight: 700; text-transform: uppercase; font-size: 10px; color: #374151;
                         margin-bottom: 4px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .chassis       { font-size: 9px; color: #6b7280; }

        /* Items table */
        .items-table             { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items-table th          { background: #1f2937; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
        .items-table th.num      { text-align: right; }
        .items-table td          { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        .items-table td.num      { text-align: right; white-space: nowrap; }
        .items-table tbody tr:nth-child(even) td { background: #f9fafb; }

        /* Financial summary */
        .totals              { width: 46%; margin-left: auto; margin-top: 12px; border-collapse: collapse; }
        .totals td           { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11.5px; }
        .totals td.num       { text-align: right; white-space: nowrap; }
        .totals tr.grand td  { font-weight: 700; font-size: 13px; background: #f3f4f6; border-top: 2px solid #374151; }
        .total-words         { margin-top: 10px; padding: 7px 10px; border: 1px solid #d1d5db;
                               background: #f9fafb; font-size: 11px; border-radius: 2px; }

        /* Signature block */
        .signatures     { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .signatures td  { width: 50%; text-align: center; font-weight: 700; font-size: 11px; }
        .signature-line { margin: 44px 28px 0; border-top: 1px solid #374151; padding-top: 5px; }
    </style>

    {{-- Template-specific overrides (child templates). May NOT redefine @page or .pdf-footer. --}}
    @stack('styles')


    {{--
        SAFE-ZONE BLOCK — always rendered LAST, cannot be overridden by child templates.

        WHY @page margin-bottom IS ZERO:
        DOMPDF positions `position:fixed` elements relative to the content area
        (inside @page margins), not the physical page. With any @page margin-bottom > 0,
        `bottom: 3mm` would place the footer 3mm above the CONTENT AREA bottom —
        which is (margin-bottom + 3mm) from the physical page bottom, creating a
        blank zone equal to the @page bottom margin.

        ARCHITECTURE:
          @page margin-bottom = 0mm
            Content area extends to the physical page bottom.
            Fixed elements with bottom:0 are at the physical page bottom.

          .pdf-footer { bottom: 3mm; height: 24mm }
            Bottom edge: 3mm from content area bottom = 3mm from physical page bottom.
            Top edge:   27mm from physical page bottom.

          body { padding-bottom: 30mm }
            Content stops 30mm from the physical page bottom.
            Gap between content bottom and footer top: 30mm - 27mm = 3mm.

        INVARIANT (do not change values independently):
          body.padding-bottom >= footer.height + footer.bottom + gap
          30mm >= 24mm + 3mm + 3mm
    --}}
    <style>
        @page {
            margin: 10mm 13mm 0mm 13mm;
        }

        /* Body safe zone — content stops before the fixed footer */
        body {
            padding-bottom: 30mm;
        }

        /* Fixed footer — rendered on every page at a fixed position */
        .pdf-footer {
            position: fixed;
            bottom: 3mm;
            left:   0;
            right:  0;
            height: 24mm;
            background: #ffffff;
            border-top: 1.5px solid #9ca3af;
            padding: 3.5mm 13mm 0 13mm;
            font-size: 8px;
            line-height: 1.4;
            color: #000000;
            opacity: 1;
            box-sizing: border-box;
        }
        .pdf-footer table { width: 100%; border-collapse: collapse; }
        .pdf-footer td    { vertical-align: top; padding: 0; color: #000000; opacity: 1; }

        /* Table pagination */
        table { page-break-inside: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tbody { page-break-inside: auto; }
        tr    { page-break-inside: avoid; break-inside: avoid; }
        td, th { overflow: hidden; }

        /* Orphan / widow control */
        p { orphans: 3; widows: 3; }

        /* Page-break protection utilities.
           DOMPDF moves the entire block to the next page if it does not fit. */
        .pdf-protect,
        .pdf-no-break,
        .totals-section,
        .comments-section,
        .payment-section,
        .signature-section {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
</head>
<body>

    @yield('content')

    {{-- Footer renders on every page via position:fixed.
         Override @section('footer-partial') in child templates for a different footer variant. --}}
    @section('footer-partial')
        @include('documents.pdf.partials._footer-legal')
    @show

</body>
</html>
