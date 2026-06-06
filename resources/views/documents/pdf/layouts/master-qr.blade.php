<!doctype html>
<html lang="{{ $document->language ?? 'fr' }}" dir="{{ ($document->language ?? 'fr') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">

    {{-- ════════════════════════════════════════════════════════════════════
         BLOCK 1 — SHARED BASE STYLES (identical to master.blade.php)
    ════════════════════════════════════════════════════════════════════ --}}
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 11px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

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

        /* Company header — flow element, first page only */
        .doc-header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 18px; }
        .company-name { font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .company-logo { max-height: 60px; max-width: 130px; object-fit: contain; }
        .header-qr { width: 58px; height: 58px; }
        .header-qr-label { font-size: 8px; text-align: center; color: #555; margin-top: 2px; }

        /* Document title */
        .doc-title { margin: 12px 0 6px; text-align: center; font-size: 20px; font-weight: 700; text-decoration: underline; text-transform: uppercase; }
        .doc-ref { text-align: center; font-size: 13px; font-weight: 700; margin-bottom: 14px; }

        /* Info boxes */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { vertical-align: top; padding-right: 10px; }
        .box { border: 1px solid #555; padding: 8px 10px; }
        .box-title { font-weight: 700; text-transform: uppercase; margin-bottom: 4px; font-size: 11px; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items-table th { background: #1f2937; color: #fff; padding: 6px 7px; font-size: 10px; text-align: left; }
        .items-table th.num { text-align: right; }
        .items-table td { border-bottom: 1px solid #d1d5db; padding: 6px 7px; vertical-align: top; }
        .items-table td.num { text-align: right; white-space: nowrap; }
        .chassis { font-size: 9px; color: #555; }

        /* Totals */
        .totals { width: 44%; margin-left: auto; margin-top: 10px; border-collapse: collapse; }
        .totals td { padding: 5px 7px; border-bottom: 1px solid #d1d5db; }
        .totals td.num { text-align: right; white-space: nowrap; }
        .totals tr.grand td { font-weight: 700; font-size: 13px; background: #f3f4f6; }
        .total-words { margin-top: 10px; padding: 7px 10px; border: 1px solid #d1d5db; background: #f9fafb; font-size: 11px; }

        /* Signatures */
        .signatures { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .signatures td { width: 50%; text-align: center; font-weight: 700; font-size: 11px; }
        .signature-line { margin: 44px 28px 0; border-top: 1px solid #111; padding-top: 5px; }

        /* QR footer image size */
        .pdf-footer .qr-img { width: 55px; height: 55px; }
    </style>

    @stack('styles')

    {{-- ════════════════════════════════════════════════════════════════════
         BLOCK 3 — IMMUTABLE SAFE-ZONE — QR FOOTER VARIANT

         Invariant:  @page margin-bottom  ==  .pdf-footer height  ==  24mm

         WHY 24mm (not 22mm):
           The QR-code image is 55px ≈ 14.6mm.
           "Vérifiez le document" text below: ~2.6mm.
           QR column total: ~17.2mm.
           + padding-top 3mm + border ~0.3mm = 20.5mm content used.
           24mm footer → 3.5mm safety buffer above content.
           Company-text column is ~9mm (shorter than QR), so QR height drives
           the required zone height.
    ════════════════════════════════════════════════════════════════════ --}}
    <style>
        @page {
            margin: 10mm 15mm 24mm 15mm;
            /*          top  lr  BOTTOM  lr
               BOTTOM (24mm) == .pdf-footer height (24mm)                      */
        }

        .pdf-footer {
            position: fixed;
            bottom: 0;
            left:   0;
            right:  0;
            height: 24mm;       /* == @page margin-bottom — DO NOT CHANGE INDEPENDENTLY */
            background: #ffffff;
            border-top: 1px solid #d1d5db;
            padding: 3mm 15mm 0 15mm;
            font-size: 8.5px;
            line-height: 1.35;
            color: #4b5563;
            box-sizing: border-box;
        }
        .pdf-footer table { width: 100%; border-collapse: collapse; }

        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr    { page-break-inside: avoid; page-break-after: auto; }
        .pdf-protect { page-break-inside: avoid; }
        p { orphans: 3; widows: 3; }
    </style>
</head>
<body>

    @yield('content')

    {{-- QR footer — default for this master variant --}}
    @section('footer-partial')
        <div class="pdf-footer">
            <table>
                <tr>
                    <td style="width: 78%; vertical-align: top;">
                        {{ $company->footer ?: $company->invoice_footer }}
                        <br>
                        {{ __('messages.bank_details') }}: {{ $company->bank_name }} {{ $company->rib }}
                    </td>
                    <td style="width: 22%; text-align: right; vertical-align: top;">
                        <img class="qr-img" src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="{{ __('messages.qr_verification') }}">
                        <div>{{ __('messages.verify_document') }}</div>
                    </td>
                </tr>
            </table>
        </div>
    @show

</body>
</html>
