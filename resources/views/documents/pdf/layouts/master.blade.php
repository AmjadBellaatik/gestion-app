<!doctype html>
<html lang="{{ $document->language ?? 'fr' }}" dir="{{ ($document->language ?? 'fr') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">

    {{-- ════════════════════════════════════════════════════════════════════
         BLOCK 1 — SHARED BASE STYLES
         Defines common typography, layout primitives, and document
         components shared by every template that extends this layout.
         Child templates may override these through @push('styles').
    ════════════════════════════════════════════════════════════════════ --}}
    <style>
        /* ── Typography ──────────────────────────────────────────────────────── */
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        /* ── Watermark (stays behind all content on every page) ──────────────── */
        .pdf-watermark {
            position: fixed;
            top: 43%;
            left: 0;
            right: 0;
            z-index: -1;
            text-align: center;
            font-size: 52px;
            font-weight: 700;
            color: #eef2f7;
            transform: rotate(-28deg);
        }

        /* ── Company header — first page only (in normal flow) ───────────────── */
        .doc-header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 18px; }
        .company-name { font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .company-logo { max-height: 60px; max-width: 130px; object-fit: contain; }
        .header-qr { width: 58px; height: 58px; }
        .header-qr-label { font-size: 8px; text-align: center; color: #555; margin-top: 2px; }

        /* ── Document title and reference ─────────────────────────────────────── */
        .doc-title {
            margin: 12px 0 6px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-ref {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        /* ── Info boxes ───────────────────────────────────────────────────────── */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { vertical-align: top; padding-right: 10px; }
        .box { border: 1px solid #555; padding: 8px 10px; }
        .box-title { font-weight: 700; text-transform: uppercase; margin-bottom: 4px; font-size: 11px; }
        .chassis { font-size: 9px; color: #555; }

        /* ── Items table ──────────────────────────────────────────────────────── */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items-table th { background: #1f2937; color: #fff; padding: 6px 7px; font-size: 10px; text-align: left; }
        .items-table th.num { text-align: right; }
        .items-table td { border-bottom: 1px solid #d1d5db; padding: 6px 7px; vertical-align: top; }
        .items-table td.num { text-align: right; white-space: nowrap; }

        /* ── Financial summary ────────────────────────────────────────────────── */
        .totals { width: 44%; margin-left: auto; margin-top: 10px; border-collapse: collapse; }
        .totals td { padding: 5px 7px; border-bottom: 1px solid #d1d5db; }
        .totals td.num { text-align: right; white-space: nowrap; }
        .totals tr.grand td { font-weight: 700; font-size: 13px; background: #f3f4f6; }
        .total-words { margin-top: 10px; padding: 7px 10px; border: 1px solid #d1d5db; background: #f9fafb; font-size: 11px; }

        /* ── Signature block ──────────────────────────────────────────────────── */
        .signatures { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .signatures td { width: 50%; text-align: center; font-weight: 700; font-size: 11px; }
        .signature-line { margin: 44px 28px 0; border-top: 1px solid #111; padding-top: 5px; }
    </style>

    {{-- ════════════════════════════════════════════════════════════════════
         BLOCK 2 — TEMPLATE-SPECIFIC STYLES
         Pushed by child templates via @push('styles').
         May define document-specific colors, special components, font
         overrides, etc.  Must NOT redefine @page or .pdf-footer.
    ════════════════════════════════════════════════════════════════════ --}}
    @stack('styles')

    {{-- ════════════════════════════════════════════════════════════════════
         BLOCK 3 — IMMUTABLE SAFE-ZONE (always last — always wins)

         WHY THIS BLOCK EXISTS AND WHY IT MUST NEVER CHANGE:

         DOMPDF uses @page margin-bottom to define the body content area.
         Body content flows from the top margin to (page-bottom – margin-bottom).
         The .pdf-footer is position:fixed at bottom:0 with height equal to
         the @page margin-bottom value.

         Result: The body content area ends exactly where the footer begins.
         Content can NEVER enter the footer zone, regardless of document
         length, because the two boundaries are the same physical point.

         The invariant that must be preserved across any future change:

             @page margin-bottom  ==  .pdf-footer height  ==  22mm

         If you change one value, you MUST change the other.
         If you do not, content WILL overlap the footer.

         This block is placed LAST in <head> so that:
           – @page cascade position guarantees it overrides any accidental
             @page rule pushed by a child template.
           – .pdf-footer cannot be overridden by template styles.

         DO NOT MOVE THIS BLOCK.
         DO NOT ADD @page RULES IN CHILD TEMPLATES.
         DO NOT CHANGE .pdf-footer HEIGHT WITHOUT CHANGING @page margin-bottom.
    ════════════════════════════════════════════════════════════════════ --}}
    <style>
        /* ── @page: standard margins — no reserved footer zone needed ──────────── */
        @page {
            margin: 10mm 12mm 10mm 12mm;
        }

        /* ── Footer: normal document flow, appears right after last content ────── */
        .pdf-footer {
            display: block;
            width: 100%;
            margin-top: 8mm;
            border-top: 1px solid #9ca3af;
            padding-top: 4mm;
            font-size: 8.5px;
            line-height: 1.4;
            color: #4b5563;
            page-break-inside: avoid;
        }
        .pdf-footer table { width: 100%; border-collapse: collapse; }

        /* ── Table page-break rules ───────────────────────────────────────────── */
        /*
           thead:display:table-header-group — column headers repeat on every page.
           tfoot:display:table-footer-group — table footers repeat on every page.
           tr:page-break-inside:avoid       — no row is ever split mid-row.
           break-inside:avoid is the CSS4 alias; both are set for DOMPDF coverage.
        */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr    { page-break-inside: avoid; break-inside: avoid; page-break-after: auto; }
        td, th { overflow: hidden; }

        /* ── Block protection ─────────────────────────────────────────────────── */
        /*
           .pdf-protect: keeps a group of elements on the same page.
           Use it to wrap: totals + total-in-words + signatures.
           If the block is too tall to fit in remaining space, DOMPDF pushes
           the entire block to the next page — never splits it into the footer.
        */
        .pdf-protect { page-break-inside: avoid; }

        /* ── Paragraph widows/orphans ─────────────────────────────────────────── */
        p { orphans: 3; widows: 3; }
    </style>
</head>
<body>

    {{-- ================================================================
         CONTENT ZONE — rendered by child templates via @section('content')
         Everything here flows within the body content area defined by
         @page margins.  The footer is rendered AFTER content by this
         master, guaranteeing it is never accidentally omitted.
    ================================================================ --}}
    @yield('content')

    {{-- ================================================================
         FOOTER — rendered by master, not overridable by content templates.
         Uses the .pdf-footer safe-zone class defined above.
         Override @section('footer-partial') in child to swap the footer
         HTML (e.g. for documents that need a QR-code footer variant).
    ================================================================ --}}
    @section('footer-partial')
        @include('documents.pdf.partials._footer-legal')
    @show

</body>
</html>
