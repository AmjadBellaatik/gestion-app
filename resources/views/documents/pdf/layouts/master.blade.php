doctype html>
<html lang="{{ $document->language ?? 'fr' }}" dir="{{ ($document->language ?? 'fr') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         BASE STYLES â€” shared by every document template
         Child templates may add overrides via @push('styles').
         Rules in Block 3 (safe-zone) are immutable and always win.
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <style>
        /* â”€â”€ Typography â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* â”€â”€ Watermark (fixed, behind all content, every page) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           DOCUMENT HEADER
           Layout: brand block (logo + name) on the LEFT, QR code on the RIGHT.
           The brand block is a self-contained left-aligned vertical column.
           Logo and company name are centered relative to each other inside it.
           The block itself never spreads to the right half of the page.
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .doc-header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }

        /*
          Brand block sits at the LEFT edge of its table cell.
          text-align:center centres the logo <img> and the name <div>
          RELATIVE TO EACH OTHER within this fixed-width column â€” it does
          not move the block away from the left.
        */
        .doc-header-brand {
            display: block;
            width: 170px;
            text-align: center;
        }
        .company-logo {
            display: block;
            margin: 0 auto;
            max-height: 56px;
            max-width: 140px;
        }
        .company-name {
            display: block;
            margin-top: 5px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: center;
        }
        .header-qr       { width: 56px; height: 56px; display: block; margin-left: auto; }
        .header-qr-label { font-size: 7.5px; text-align: center; color: #6b7280; margin-top: 2px; }

        /* â”€â”€ Document title and reference â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .doc-title {
            margin: 14px 0 6px;
            text-align: center;
            font-size: 19px;
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .doc-ref {
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 14px;
        }

        /* â”€â”€ Info boxes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .info-table      { width: 100%; border-collapse: collapse; }
        .info-table td   { vertical-align: top; padding-right: 10px; }
        .box             { border: 1px solid #6b7280; padding: 7px 10px; border-radius: 2px; }
        .box-title       { font-weight: 700; text-transform: uppercase; font-size: 10px; color: #374151; margin-bottom: 4px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .chassis         { font-size: 9px; color: #6b7280; }

        /* â”€â”€ Items table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .items-table     { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items-table th  { background: #1f2937; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
        .items-table th.num { text-align: right; }
        .items-table td  { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        .items-table td.num { text-align: right; white-space: nowrap; }
        .items-table tbody tr:nth-child(even) td { background: #f9fafb; }

        /* â”€â”€ Financial summary â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .totals          { width: 46%; margin-left: auto; margin-top: 12px; border-collapse: collapse; }
        .totals td       { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11.5px; }
        .totals td.num   { text-align: right; white-space: nowrap; }
        .totals tr.grand td { font-weight: 700; font-size: 13px; background: #f3f4f6; border-top: 2px solid #374151; }
        .total-words     { margin-top: 10px; padding: 7px 10px; border: 1px solid #d1d5db; background: #f9fafb; font-size: 11px; border-radius: 2px; }

        /* â”€â”€ Signature block â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .signatures      { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .signatures td   { width: 50%; text-align: center; font-weight: 700; font-size: 11px; }
        .signature-line  { margin: 44px 28px 0; border-top: 1px solid #374151; padding-top: 5px; }
    </style>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         TEMPLATE-SPECIFIC STYLES â€” pushed by child templates.
         May NOT redefine @page.
         May NOT override .pdf-footer.
         May NOT override the safe-zone values.
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    @stack('styles')

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         SAFE-ZONE BLOCK â€” always rendered LAST, always wins.

         â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
         â”‚  DOMPDF FIXED-FOOTER ARCHITECTURE                           â”‚
         â”‚                                                             â”‚
         â”‚  @page margin-bottom = 30 mm                                â”‚
         â”‚    â””â”€ body content area ends 30 mm from the page bottom     â”‚
         â”‚       (DOMPDF enforces this hard boundary)                  â”‚
         â”‚                                                             â”‚
         â”‚  .pdf-footer                                                â”‚
         â”‚    bottom : 3 mm   â† gap from physical page edge           â”‚
         â”‚    height : 24 mm  â† visual footer height                  â”‚
         â”‚    top edge at 3+24 = 27 mm from page bottom               â”‚
         â”‚                                                             â”‚
         â”‚  Content stops at 30 mm.  Footer top is at 27 mm.          â”‚
         â”‚  3 mm white buffer between content and footer top.          â”‚
         â”‚                                                             â”‚
         â”‚  INVARIANT:                                                 â”‚
         â”‚    @page margin-bottom  â‰¥  footer.bottom + footer.height    â”‚
         â”‚    30 mm               â‰¥  3 mm         + 24 mm     âœ“       â”‚
         â”‚                                                             â”‚
         â”‚  DO NOT change these values independently.                  â”‚
         â”‚  DO NOT add @page rules in child templates.                 â”‚
         â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <style>
        @page {
            margin: 10mm 13mm 30mm 13mm;
            /*                   ^^^^
               30mm = footer.height(24mm) + footer.bottom(3mm) + 3mm buffer
               CHANGE THIS only if you also change .pdf-footer height/bottom */
        }

        /* â”€â”€ Fixed footer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .pdf-footer {
            position: fixed;
            bottom: 3mm;            /* gap from physical page edge */
            left:   0;
            right:  0;
            height: 24mm;           /* visual height â€” must satisfy @page invariant */
            background: #ffffff;
            border-top: 1.5px solid #9ca3af;
            padding: 3.5mm 13mm 0 13mm;  /* horizontal matches @page left/right */
            font-size: 8px;
            line-height: 1.4;
            color: #6b7280;
            box-sizing: border-box;
        }
        .pdf-footer table   { width: 100%; border-collapse: collapse; }
        .pdf-footer td      { vertical-align: top; padding: 0; }

        /* â”€â”€ Table pagination rules â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        table { page-break-inside: auto; }       /* tables may span pages        */
        thead { display: table-header-group; }   /* column headers on every page */
        tfoot { display: table-footer-group; }   /* table footers on every page  */
        tbody { page-break-inside: auto; }            /* body rows may span pages     */
        tr    { page-break-inside: avoid; break-inside: avoid; }
        td, th { overflow: hidden; }

        /* â”€â”€ Orphan / widow control â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        p { orphans: 3; widows: 3; }

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           PAGE-BREAK PROTECTION UTILITIES
           Apply any of these classes to keep a block on a single page.
           If there is not enough remaining space, DOMPDF moves the ENTIRE
           block to the next page â€” it never splits it into the footer zone.

           Usage in templates:
             <div class="pdf-protect">          general-purpose wrapper
             <div class="totals-section">        financial totals table
             <div class="comments-section">      notes / total-in-words
             <div class="payment-section">       payment schedule rows
             <div class="signature-section">     signature boxes
             <div class="pdf-no-break">          any other never-split block
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    {{-- ================================================================
         CONTENT ZONE
         Flows within the body area set by @page margins.
         The 30 mm bottom margin guarantees the footer zone is unreachable.
    ================================================================ --}}
    @yield('content')

    {{-- ================================================================
         FOOTER â€” shared partial, renders on every page via position:fixed.
         Child templates may override @section('footer-partial') only when
         a genuinely different footer variant is needed.
    ================================================================ --}}
    @section('footer-partial')
        @include('documents.pdf.partials._footer-legal')
    @show

</body>
</html>

