<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 10mm 4mm 10mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 12px;
            line-height: 1.45;
        }
        .watermark {
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
        .doc-header,
        .info-table,
        .signatures {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-header {
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .company-name {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .company-logo {
            max-height: 60px;
            max-width: 130px;
            object-fit: contain;
        }
        .header-qr {
            width: 58px;
            height: 58px;
        }
        .header-qr-label {
            font-size: 8px;
            text-align: center;
            color: #555;
            margin-top: 2px;
        }
        .title {
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
        .info-table td {
            vertical-align: top;
            padding-right: 10px;
        }
        .box {
            border: 1px solid #555;
            padding: 8px 10px;
        }
        .box-title {
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-size: 11px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .items-table th {
            background: #1f2937;
            color: #fff;
            padding: 6px 7px;
            font-size: 10px;
            text-align: left;
        }
        .items-table th.num { text-align: right; }
        .items-table td {
            border-bottom: 1px solid #d1d5db;
            padding: 6px 7px;
            vertical-align: top;
        }
        .items-table td.num { text-align: right; white-space: nowrap; }
        .totals {
            width: 44%;
            margin-left: auto;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .totals td {
            padding: 5px 7px;
            border-bottom: 1px solid #d1d5db;
        }
        .totals td.num { text-align: right; white-space: nowrap; }
        .totals tr.grand td {
            font-weight: 700;
            font-size: 13px;
            background: #f3f4f6;
        }
        .total-words {
            margin-top: 10px;
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-size: 11px;
        }
        .signatures {
            margin-top: 36px;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            font-weight: 700;
            font-size: 11px;
        }
        .signature-line {
            margin: 44px 28px 0;
            border-top: 1px solid #111;
            padding-top: 5px;
        }
        .doc-footer {
            position: fixed;
            left: 10mm;
            right: 10mm;
            bottom: 1mm;
            border-top: 1px solid #777;
            padding-top: 3px;
            font-size: 9px;
            color: #444;
        }
        .doc-footer table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
@php
    $companyName = $company->name;

    $providerName    = data_get($document->metadata, 'manual_supplier_name');
    $providerPhone   = data_get($document->metadata, 'manual_supplier_phone');
    $providerEmail   = data_get($document->metadata, 'manual_supplier_email');
    $providerAddress = data_get($document->metadata, 'manual_supplier_address');
    $providerIce     = data_get($document->metadata, 'provider_ice');
    $providerRc      = data_get($document->metadata, 'provider_rc');
    $quoteRef        = data_get($document->metadata, 'supplier_quote_number');

    $totalTtc = (float) $document->total_amount;
    if ($totalTtc <= 0 && $document->items->isNotEmpty()) {
        $totalTtc = (float) $document->items->sum(fn ($item) => (float) $item->total);
    }
    $taxAmount = $totalTtc > 0
        ? round($totalTtc * (20 / 120), 2)
        : (float) $document->tax_amount;
    $subtotal = $totalTtc > 0
        ? round($totalTtc - $taxAmount, 2)
        : (float) $document->subtotal;

    $watermarkText = strtoupper($companyName);
@endphp

    <div class="watermark">{{ $watermarkText }}</div>

    <table class="doc-header">
        <tr>
            <td style="width: 75%; vertical-align: middle;">
                @if($company->logo)
                <img class="company-logo" src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $companyName }}"><br>
                @endif
                <div class="company-name">{{ $companyName }}</div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                <img class="header-qr" src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR">
                <div class="header-qr-label">{{ __('messages.verify_document') }}</div>
            </td>
        </tr>
    </table>

    <div class="title">{{ __('messages.supplier_order') }}</div>
    <div class="doc-ref">
        {{ __('messages.document_number') }} : {{ $document->document_number }}
        &nbsp;|&nbsp;
        {{ __('messages.document_date') }} : {{ $document->document_date?->format('d/m/Y') }}
        @if($quoteRef)
            &nbsp;|&nbsp; {{ __('messages.provider_quote_reference') }} : {{ $quoteRef }}
        @endif
    </div>

    <table class="info-table" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.provider') }}</div>
                    <strong>{{ $providerName }}</strong><br>
                    @if($providerAddress){{ $providerAddress }}<br>@endif
                    @if($providerPhone){{ __('messages.phone') }}: {{ $providerPhone }}<br>@endif
                    @if($providerEmail){{ __('messages.email') }}: {{ $providerEmail }}<br>@endif
                    @if($providerIce){{ __('messages.ice') }}: {{ $providerIce }}<br>@endif
                    @if($providerRc){{ __('messages.rc') }}: {{ $providerRc }}@endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.delivery_address') }}</div>
                    <strong>{{ $companyName }}</strong><br>
                    {{ $company->address ?: $company->legal_address }}<br>
                    @if($company->city){{ strtoupper($company->city) }}<br>@endif
                    @if($company->phone){{ __('messages.phone') }}: {{ $company->phone }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('messages.description') }}</th>
                <th class="num">{{ __('messages.quantity') }}</th>
                <th class="num">{{ __('messages.unit_price_ttc') }}</th>
                <th class="num">{{ __('messages.tax_rate') }}</th>
                <th class="num">{{ __('messages.total_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
            <tr>
                <td><strong>{{ $item->description }}</strong></td>
                <td class="num">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                <td class="num">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD</td>
                <td class="num">20%</td>
                <td class="num">{{ number_format((float) $item->total, 2, ',', ' ') }} MAD</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('messages.subtotal_ht') }}</td>
            <td class="num">{{ number_format($subtotal, 2, ',', ' ') }} MAD</td>
        </tr>
        <tr>
            <td>{{ __('messages.tva_20') }}</td>
            <td class="num">{{ number_format($taxAmount, 2, ',', ' ') }} MAD</td>
        </tr>
        <tr class="grand">
            <td>{{ __('messages.total_ttc') }}</td>
            <td class="num">{{ number_format($totalTtc, 2, ',', ' ') }} MAD</td>
        </tr>
    </table>

    <div class="total-words">
        {{ __('messages.total_in_letters') }} : <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
    </div>

    <table class="signatures">
        <tr>
            <td><div class="signature-line">{{ __('messages.supplier_signature') }}</div></td>
            <td><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
        </tr>
    </table>

    <div class="doc-footer">
        <table>
            <tr>
                <td style="width: 50%;">
                    {{ $company->address ?: $company->legal_address }}
                    @if($company->city) — {{ strtoupper($company->city) }}@endif
                </td>
                <td style="width: 50%; text-align: right;">
                    @if($company->phone) Tél : {{ $company->phone }} @endif
                    @if($company->email) | {{ $company->email }} @endif
                </td>
            </tr>
            <tr>
                <td>
                    @if($company->ice) ICE : {{ $company->ice }} @endif
                    @if($company->rc) | RC : {{ $company->rc }} @endif
                    @if($company->if) | IF : {{ $company->if }} @endif
                </td>
                <td style="text-align: right;">
                    @if($company->patente) Patente : {{ $company->patente }} @endif
                    @if($company->cnss) | CNSS : {{ $company->cnss }} @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
