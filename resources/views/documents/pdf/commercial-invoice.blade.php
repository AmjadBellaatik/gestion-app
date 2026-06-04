<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 10mm 24mm 10mm; }
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
        .chassis {
            font-size: 9px;
            color: #555;
        }
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
        .total-words {
            margin-top: 10px;
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-size: 11px;
        }
        .doc-footer {
            position: fixed;
            left: 10mm;
            right: 10mm;
            bottom: 5mm;
            height: 16mm;
            border-top: 1px solid #777;
            padding-top: 5px;
            font-size: 9px;
            line-height: 1.3;
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
    $buyer = $document->reseller ?: $document->sale?->reseller ?: $client;
    $isResellerBuyer = $buyer instanceof \App\Models\Reseller;
    $clientType  = $client?->client_type ?: 'person';
    $clientName  = match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => $client?->display_name,
    };
    $buyerName = $isResellerBuyer
        ? $buyer?->name
        : $clientName;
    $purchaseOrderNumber = data_get($document->metadata, 'purchase_order_number')
        ?: $document->sale?->purchase_order_number;

    // Discount from the linked sale
    $discount     = $document->sale ? max(0.0, (float) $document->sale->discount) : 0.0;
    $discountNote = $document->sale?->discount_note;

    // Totals — prefer stored values, fallback to summing items
    // $totalTtc = NET total (after discount already applied)
    $totalTtc = (float) $document->total_amount;
    if ($totalTtc <= 0 && $document->items->isNotEmpty()) {
        $totalTtc = (float) $document->items->sum(fn ($item) => (float) $item->total);
    }
    if ($totalTtc <= 0 && $document->sale) {
        $totalTtc = (float) ($document->sale->total ?: $document->sale->paid_amount);
    }
    // Gross total before discount
    $grossTtc  = $discount > 0 ? round($totalTtc + $discount, 2) : $totalTtc;
    $taxAmount = $totalTtc > 0
        ? round($totalTtc * (20 / 120), 2)
        : (float) $document->tax_amount;
    $subtotal = $totalTtc > 0
        ? round($totalTtc - $taxAmount, 2)
        : (float) $document->subtotal;

    // Watermark
    $firstUnit = $document->items->first(fn ($i) => $i->motorcycleUnit)?->motorcycleUnit;
    $watermarkText = $firstUnit?->motorcycleModel?->brand?->name
        ?: $firstUnit?->motorcycleModel?->marque
        ?: $companyName;
@endphp

    <div class="watermark">{{ strtoupper($watermarkText) }}</div>

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

    <div class="title">{{ __('messages.invoice') }}</div>
    <div class="doc-ref">
        {{ __('messages.document_number') }} : {{ $document->document_number }}
        &nbsp;|&nbsp;
        {{ __('messages.document_date') }} : {{ $document->document_date?->format('d/m/Y') }}
        @if($purchaseOrderNumber)
            &nbsp;|&nbsp; {{ __('messages.purchase_order') }} : {{ $purchaseOrderNumber }}
        @endif
    </div>

    <table class="info-table" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.client') }}</div>
                    <strong>{{ $buyerName }}</strong><br>
                    @if($isResellerBuyer)
                        @if($buyer?->ice){{ __('messages.ice') }}: {{ $buyer->ice }}<br>@endif
                        @if($buyer?->phone){{ __('messages.phone') }}: {{ $buyer->phone }}<br>@endif
                    @elseif(in_array($clientType, ['company', 'administration']))
                        @if($client?->ice){{ __('messages.ice') }}: {{ $client->ice }}<br>@endif
                        @if($client?->phone){{ __('messages.phone') }}: {{ $client->phone }}<br>@endif
                    @else
                        @if($client?->cin)CIN: {{ $client->cin }}<br>@endif
                    @endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.payment_information') }}</div>
                    @if($purchaseOrderNumber)
                        <strong>{{ __('messages.purchase_order') }} : {{ $purchaseOrderNumber }}</strong><br>
                    @endif
                    @if($company->bank_name || $company->rib)
                        {{ __('messages.bank_details') }}: {{ $company->bank_name }} {{ $company->rib }}
                    @endif
                    @if($document->sale?->sale_number)
                        <br>{{ __('messages.sale') }}: {{ $document->sale->sale_number }}
                    @endif
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
                <td>
                    <strong>{{ $item->description }}</strong>
                    @if($item->motorcycleUnit)
                        <br><span class="chassis">{{ __('messages.chassis_number') }}: {{ $item->motorcycleUnit->chassis_number }}</span>
                    @endif
                </td>
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
        @if($discount > 0)
        <tr>
            <td>{{ __('messages.gross_total') }}</td>
            <td class="num">{{ number_format($grossTtc, 2, ',', ' ') }} MAD</td>
        </tr>
        <tr>
            <td style="color:#b45309; font-weight:600;">
                {{ __('messages.discount_amount') }}
                @if($discountNote) <br><span style="font-weight:400; font-size:10px;">{{ $discountNote }}</span>@endif
            </td>
            <td class="num" style="color:#b45309; font-weight:600;">- {{ number_format($discount, 2, ',', ' ') }} MAD</td>
        </tr>
        <tr class="grand">
            <td>{{ __('messages.net_total_after_discount') }}</td>
            <td class="num">{{ number_format($totalTtc, 2, ',', ' ') }} MAD</td>
        </tr>
        @else
        <tr class="grand">
            <td>{{ __('messages.total_ttc') }}</td>
            <td class="num">{{ number_format($totalTtc, 2, ',', ' ') }} MAD</td>
        </tr>
        @endif
    </table>

    <div class="total-words">
        Arrêté la présente Facture à la somme TTC de : <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
    </div>

    <table class="signatures">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%;"><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
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
