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
        .total-words {
            margin-top: 10px;
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-size: 11px;
        }
        .tech-title {
            margin-top: 18px;
            padding: 7px 9px;
            background: #1f2937;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            page-break-after: avoid;
        }
        .tech-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .tech-table td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            vertical-align: top;
            font-size: 11px;
        }
        .tech-table td.label {
            width: 23%;
            background: #f3f4f6;
            font-weight: 700;
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

    // Quotation uses manual (metadata) client info, not a linked client
    $manualClientType = data_get($document->metadata, 'manual_client_type', 'person');
    $manualClientName = match ($manualClientType) {
        'company'        => data_get($document->metadata, 'manual_client_company_name'),
        'administration' => data_get($document->metadata, 'manual_client_administration_name'),
        default          => trim(data_get($document->metadata, 'manual_client_first_name', '') . ' ' . data_get($document->metadata, 'manual_client_last_name', '')),
    } ?: data_get($document->metadata, 'manual_client_name');
    $manualClientPhone   = data_get($document->metadata, 'manual_client_phone');
    $manualClientEmail   = data_get($document->metadata, 'manual_client_email');
    $manualClientAddress = data_get($document->metadata, 'manual_client_address');
    $clientTitle = match ($manualClientType) {
        'company'        => __('messages.company'),
        'administration' => __('messages.administration'),
        default          => __('messages.client'),
    };

    // Totals
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

    // Watermark
    $firstUnit     = $document->items->first(fn ($i) => $i->motorcycleUnit)?->motorcycleUnit;
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

    <div class="title">{{ __('messages.quotation') }}</div>
    <div class="doc-ref">
        {{ __('messages.document_number') }} : {{ $document->document_number }}
        &nbsp;|&nbsp;
        {{ __('messages.document_date') }} : {{ $document->document_date?->format('d/m/Y') }}
    </div>

    <table class="info-table" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 100%;">
                <div class="box">
                    <div class="box-title">{{ $clientTitle }}</div>
                    <strong>{{ $manualClientName }}</strong><br>
                    @if($manualClientAddress){{ $manualClientAddress }}<br>@endif
                    @if($manualClientPhone){{ __('messages.phone') }}: {{ $manualClientPhone }}<br>@endif
                    @if($manualClientEmail){{ __('messages.email') }}: {{ $manualClientEmail }}@endif
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
        <tr class="grand">
            <td>{{ __('messages.total_ttc') }}</td>
            <td class="num">{{ number_format($totalTtc, 2, ',', ' ') }} MAD</td>
        </tr>
    </table>

    <div class="total-words">
        Arrêté le présent Devis à la somme TTC de : <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
    </div>

    {{-- Motorcycle technical specs --}}
    @foreach($document->items as $item)
        @php
            $unit  = $item->motorcycleUnit;
            $model = $unit?->motorcycleModel;
            $homologation = $model?->homologation;
            $wheelbase = collect([$model?->empattement_1_2, $model?->empattement_2_3, $model?->empattement_3_4])
                ->filter()->implode(' / ');
            $techRows = [
                ['MARQUE',            $model?->marque,                    'Type',                  $model?->type],
                ['Modele',            $model?->modele,                    'Moteur',                 $model?->cylindree],
                ['Refroidissement',   data_get($model, 'refroidissement'), 'Alesage / Course',      ($model?->alesage ?? '-') . ' / ' . ($model?->course ?? '-')],
                ['Categorie',         $model?->categorie,                 'Cylindres',              $model?->nombre_cylindres],
                ['Puissance Fiscale', $model?->puissance_fiscale,         'PAV Avant / Arriere',    ($model?->pav_avant ?? '-') . ' / ' . ($model?->pav_arriere ?? '-')],
                ['Poids a vide',      $model?->poids_vide_total,          'PTC Avant / Arriere',    ($model?->ptc_avant ?? '-') . ' / ' . ($model?->ptc_arriere ?? '-')],
                ['PTAC',              $model?->ptac,                      'Boite a vitesse',        $model?->boite_vitesse],
                ['Empattement',       $wheelbase,                         'Pneu AV / AR',           ($model?->pneu_avant ?? '-') . ' / ' . ($model?->pneu_arriere ?? '-')],
                ['Carburant',         $model?->carburant,                 'Nombre de places',       $model?->nombre_places],
                ['Homologation',      $model?->titre_homologation ?: $homologation?->homologation_number, 'Demarrage', data_get($model, 'demarrage')],
            ];
        @endphp
        @if($unit)
            <div class="tech-title">{{ __('messages.motorcycle') }} — {{ $model?->marque }} {{ $model?->modele }}</div>
            <table class="tech-table">
                @foreach($techRows as $row)
                <tr>
                    <td class="label">{{ $row[0] }}</td>
                    <td>{{ $row[1] ?: '-' }}</td>
                    <td class="label">{{ $row[2] }}</td>
                    <td>{{ $row[3] ?: '-' }}</td>
                </tr>
                @endforeach
            </table>
        @endif
    @endforeach

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
