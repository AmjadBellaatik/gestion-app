<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 10mm 4mm 10mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 13px;
            line-height: 1.48;
        }
        .watermark {
            position: fixed;
            top: 43%;
            left: 0;
            right: 0;
            z-index: -1;
            text-align: center;
            font-size: 50px;
            font-weight: 700;
            color: #eef2f7;
            transform: rotate(-28deg);
        }
        .doc-header,
        .vehicle-table,
        .signatures {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-header {
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
            margin-bottom: 22px;
        }
        .company-name {
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .company-logo {
            max-height: 64px;
            max-width: 140px;
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
            margin: 14px 0 22px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .intro {
            margin-bottom: 16px;
            text-align: justify;
        }
        .vehicle-table td {
            padding: 7px 9px;
            border: 1px solid #444;
            vertical-align: top;
        }
        .label {
            width: 28%;
            font-weight: 700;
            background: #f3f4f6;
        }
        .value {
            font-weight: 700;
        }
        .party {
            margin-top: 16px;
            padding: 10px 12px;
            border: 1px solid #555;
        }
        .party-title {
            margin-bottom: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .terms {
            margin-top: 18px;
            text-align: justify;
        }
        .date-place {
            margin-top: 20px;
            text-align: right;
            font-weight: 700;
        }
        .signatures {
            margin-top: 42px;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            font-weight: 700;
        }
        .signature-line {
            margin: 48px 28px 0;
            border-top: 1px solid #111;
            padding-top: 6px;
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
    </style>
</head>
<body>
@php
    $unit = $motorcycleUnit
        ?: $document->items->first(fn ($item) => $item->motorcycleUnit)?->motorcycleUnit
        ?: $document->primaryMotorcycleUnit();
    $model = $unit?->motorcycleModel;
    $product = $document->items->first(fn ($item) => $item->product)?->product;
    $coveredItemName = $unit
        ? trim(($model?->marque ? $model->marque . ' ' : '') . ($model?->modele ?: ''))
        : $product?->name;
    $coveredItemType = $unit ? $model?->type : ($product?->type ? __('messages.' . $product->type) : null);
    $coveredItemReference = $unit ? $unit?->chassis_number : ($product?->sku ?: $product?->barcode);
    $companyName = $company->name;
    $clientType = $client?->client_type;
    $clientName = match ($clientType) {
        'company' => $client?->company_name,
        'administration' => $client?->administration_name,
        default => trim(($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')),
    };
    $clientIdentity = $clientType === 'company'
        ? $client?->rc
        : ($clientType === 'administration' ? null : ($client?->rc ?: $client?->cin));
    $warrantyDurationValue = data_get($document->metadata, 'warranty_duration_value')
        ?: data_get($document->metadata, 'warranty_years');
    $warrantyDurationUnit = data_get($document->metadata, 'warranty_duration_unit', 'years');
    $warrantyDurationLabel = trim($warrantyDurationValue . ' ' . __('messages.' . $warrantyDurationUnit));
    $warrantyKilometers = data_get($document->metadata, 'warranty_kilometers');
    $city = \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($company->city ?: 'Sale'));
    $brand = $model?->brand?->name ?: $model?->marque;
    $isElectricOrScooter = !$unit && $product && in_array($product->type, ['trotinette', 'velo_electrique']);
@endphp

    <div class="watermark">{{ strtoupper($brand ?? $companyName) }}</div>

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

    <div class="title">Contrat de garantie</div>

    <p class="intro">
        Ce contrat presente et explique les pieces de garantie du vehicule. Il est etabli entre les deux parties
        designees ci-dessous et concerne le vehicule identifie par les informations suivantes :
    </p>

    <table class="vehicle-table">
        @if($unit)
            <tr>
                <td class="label">MARQUE</td>
                <td class="value">{{ $model?->marque }}</td>
                <td class="label">Modele</td>
                <td class="value">{{ $model?->modele }}</td>
            </tr>
            <tr>
                <td class="label">No Chassis</td>
                <td class="value">{{ $unit?->chassis_number }}</td>
                <td class="label">Type</td>
                <td class="value">{{ $model?->type }}</td>
            </tr>
        @else
            <tr>
                <td class="label">{{ $coveredItemType }}</td>
                <td class="value">{{ $coveredItemName }}</td>
                <td class="label">{{ __('messages.type') }}</td>
                <td class="value">{{ $coveredItemType }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.sku') }} / {{ __('messages.barcode') }}</td>
                <td class="value" colspan="3">{{ $coveredItemReference }}</td>
            </tr>
        @endif
    </table>

    <div class="party">
        <div class="party-title">D'une part</div>
        <div>SOCIETE <span class="value">{{ $companyName }}</span></div>
        <div>{{ $company->address ?: $company->legal_address }}</div>
    </div>

    <div class="party">
        <div class="party-title">D'autre part</div>
        <div><span class="value">{{ $clientName }}</span></div>
        @if($clientIdentity)
            <div>{{ __('messages.cin') }} / {{ __('messages.rc') }} : {{ $clientIdentity }}</div>
        @endif
        <div>{{ $client?->address }}</div>
    </div>

    <div class="terms">
        @if($isElectricOrScooter)
            <p>Il a été convenu et arrêté ce qui suit :</p>
            <p>
                SOCIETE <strong>{{ $companyName }}</strong> s'engage à accorder une garantie sur le moteur,
                le contrôleur, la batterie ainsi que les composants électriques d'origine du véhicule
                à compter de la date de livraison.
            </p>
            <p>Le chargeur bénéficie d'une garantie limitée à 48 heures après livraison.</p>
            <p>
                Sont exclus de la garantie les pièces d'usure et consommables tels que : pneus, chambres à air,
                plaquettes de frein, poignées, câbles, ampoules et accessoires.
            </p>
            <p>
                La garantie ne couvre pas les dommages résultant d'un accident, d'une chute, d'une mauvaise
                utilisation, d'une modification non autorisée, d'une infiltration d'eau ou d'un vol du véhicule
                ou de ses composants.
            </p>
        @else
            <p>
                Il a ete convenu et arrete ce qui suit : SOCIETE <strong>{{ $companyName }}</strong>
                s'engage de donner <strong>{{ $warrantyDurationLabel }}</strong> de garantie pour
                <strong>{{ $coveredItemName }}</strong>
                ou <strong>{{ $warrantyKilometers }}</strong> KM a compter de la date de livraison
                (par exemple : cylindre, vilebrequin...).
            </p>
            <p>
                Cette garantie ne contient pas les frais de la main d'oeuvre, les cables et l'equipement electronique,
                plaquettes de frein, pneumatique et les articles fournis sur demande speciale de l'acheteur, ni la tenue
                de la peinture, du nickel ou du chrome.
            </p>
        @endif
    </div>

    <div class="date-place">{{ $city }} le : {{ $document->document_date?->format('d/m/Y') }}</div>

    <table class="signatures">
        <tr>
            <td><div class="signature-line">{{ __('messages.client_signature') }}</div></td>
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
