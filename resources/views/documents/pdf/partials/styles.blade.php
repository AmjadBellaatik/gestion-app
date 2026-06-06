<style>
    @php
        $primaryColor   = $company?->primary_color   ?: '#111827';
        $secondaryColor = $company?->secondary_color ?: '#111827';
        $accentColor    = $company?->accent_color    ?: '#f3f4f6';
    @endphp

    /* ── Safe-Zone Layout ─────────────────────────────────────────────────────
       @page margin-bottom (24mm) = .footer height (24mm).
       Body content area ends at 24mm from the physical page bottom.
       The fixed footer with background:white covers that zone on every page.
    ────────────────────────────────────────────────────────────────────────── */
    @page { margin: 10mm 15mm 24mm 15mm; }

    body { font-family: DejaVu Sans, sans-serif; color: {{ $secondaryColor }}; font-size: 11px; line-height: 1.35; }
    .rtl { direction: rtl; text-align: right; }

    /* ── Header (normal flow — renders once on page 1) ─────────────────────── */
    .header { border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 10px; margin-bottom: 18px; }
    .company-logo { width: 72px; height: 72px; object-fit: contain; margin-bottom: 6px; }
    .company-heading { display: table; width: 100%; }
    .company-heading-logo { display: table-cell; width: 84px; vertical-align: top; }
    .company-heading-info { display: table-cell; vertical-align: top; }
    .brand { color: {{ $primaryColor }}; font-size: 20px; font-weight: 700; letter-spacing: .3px; }

    /* ── Typography helpers ─────────────────────────────────────────────────── */
    .muted  { color: #6b7280; }
    .small  { font-size: 9px; }
    .title  { font-size: 22px; font-weight: 700; text-transform: uppercase; text-align: right; }

    /* ── Layout primitives ──────────────────────────────────────────────────── */
    .grid { width: 100%; border-collapse: collapse; }
    .grid td { vertical-align: top; }
    .box  { border: 1px solid {{ $primaryColor }}; padding: 8px; }

    /* ── Items table ────────────────────────────────────────────────────────── */
    .items { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .items th { background: {{ $primaryColor }}; color: white; padding: 7px; font-size: 10px; text-align: left; }
    .items td { border-bottom: 1px solid #d1d5db; padding: 7px; }
    .items .num { text-align: right; white-space: nowrap; }

    /* ── Totals ─────────────────────────────────────────────────────────────── */
    .totals { width: 42%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
    .totals td { padding: 6px; border-bottom: 1px solid #d1d5db; }
    .totals .grand { font-size: 13px; font-weight: 700; background: {{ $accentColor }}; }

    /* ── Signatures ─────────────────────────────────────────────────────────── */
    .signatures { width: 100%; margin-top: 36px; border-collapse: collapse; page-break-inside: avoid; }
    .signatures td { width: 50%; height: 70px; vertical-align: bottom; text-align: center; }

    /* ── QR ─────────────────────────────────────────────────────────────────── */
    .qr { width: 60px; height: 60px; }

    /* ── Official document styles ───────────────────────────────────────────── */
    .official-title { text-align: center; font-size: 15px; font-weight: 700; text-transform: uppercase; margin: 12px 0; }
    .official-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .official-table td, .official-table th { border: 1px solid {{ $primaryColor }}; padding: 5px; }
    .official-table th { background: {{ $accentColor }}; text-align: center; }

    /* ── Watermark ──────────────────────────────────────────────────────────── */
    .watermark { position: fixed; inset: 0; z-index: -1; opacity: .08; font-size: 80px; text-align: center; padding-top: 310px; transform: rotate(-25deg); }

    /* ── Footer Safe Zone ───────────────────────────────────────────────────── */
    /* height (24mm) = @page margin-bottom (24mm): guaranteed safe zone */
    .footer {
        position: fixed;
        bottom: 0;
        left:   0;
        right:  0;
        height: 24mm;            /* must equal @page margin-bottom */
        background: #ffffff;     /* physical barrier over the margin zone */
        border-top: 1px solid #d1d5db;
        padding: 3mm 15mm 0 15mm;
        font-size: 8.5px;
        color: #4b5563;
        line-height: 1.35;
    }
    .footer table { width: 100%; border-collapse: collapse; }
    .footer .qr   { width: 55px; height: 55px; }

    /* ── Page-break safety ──────────────────────────────────────────────────── */
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr    { page-break-inside: avoid; page-break-after: auto; }
    .pdf-protect { page-break-inside: avoid; }
</style>
