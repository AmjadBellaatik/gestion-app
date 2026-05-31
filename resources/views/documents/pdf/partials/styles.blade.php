<style>
    @php
        $primaryColor = $company?->primary_color ?: '#111827';
        $secondaryColor = $company?->secondary_color ?: '#111827';
        $accentColor = $company?->accent_color ?: '#f3f4f6';
    @endphp

    @page { margin: 28mm 20mm 28mm 20mm; }
    body { font-family: DejaVu Sans, sans-serif; color: {{ $secondaryColor }}; font-size: 11px; line-height: 1.35; }
    .rtl { direction: rtl; text-align: right; }
    .header { border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 10px; margin-bottom: 18px; }
    .company-logo { width: 72px; height: 72px; object-fit: contain; margin-bottom: 6px; }
    .company-heading { display: table; width: 100%; }
    .company-heading-logo { display: table-cell; width: 84px; vertical-align: top; }
    .company-heading-info { display: table-cell; vertical-align: top; }
    .brand { color: {{ $primaryColor }}; font-size: 20px; font-weight: 700; letter-spacing: .3px; }
    .muted { color: #6b7280; }
    .small { font-size: 9px; }
    .title { font-size: 22px; font-weight: 700; text-transform: uppercase; text-align: right; }
    .grid { width: 100%; border-collapse: collapse; }
    .grid td { vertical-align: top; }
    .box { border: 1px solid {{ $primaryColor }}; padding: 8px; }
    .items { width: 100%; border-collapse: collapse; margin-top: 16px; page-break-inside: auto; }
    .items tr { page-break-inside: avoid; page-break-after: auto; }
    .items th { background: {{ $primaryColor }}; color: white; padding: 7px; font-size: 10px; text-align: left; }
    .items td { border-bottom: 1px solid #d1d5db; padding: 7px; }
    .items .num { text-align: right; white-space: nowrap; }
    .totals { width: 42%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
    .totals td { padding: 6px; border-bottom: 1px solid #d1d5db; }
    .totals .grand { font-size: 13px; font-weight: 700; background: {{ $accentColor }}; }
    .signatures { width: 100%; margin-top: 36px; border-collapse: collapse; page-break-inside: avoid; }
    .signatures td { width: 50%; height: 70px; vertical-align: bottom; text-align: center; }
    .footer { position: fixed; left: 28px; right: 28px; bottom: 12px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8.5px; color: #4b5563; }
    .qr { width: 70px; height: 70px; }
    .official-title { text-align: center; font-size: 15px; font-weight: 700; text-transform: uppercase; margin: 12px 0; }
    .official-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .official-table td, .official-table th { border: 1px solid {{ $primaryColor }}; padding: 5px; }
    .official-table th { background: {{ $accentColor }}; text-align: center; }
    .watermark { position: fixed; inset: 0; z-index: -1; opacity: .08; font-size: 80px; text-align: center; padding-top: 310px; transform: rotate(-25deg); }
</style>
