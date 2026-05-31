<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 10mm 4mm 10mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 14px;
            font-style: italic;
            line-height: 1.33;
        }
        .background {
            position: fixed;
            top: 41%;
            left: 0;
            right: 0;
            z-index: -1;
            text-align: center;
            font-size: 58px;
            font-style: normal;
            font-weight: 700;
            color: #eef2f7;
            transform: rotate(-28deg);
        }
        .doc-header {
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
            margin-bottom: 28px;
            font-style: normal;
        }
        .doc-header table,
        .doc-footer table {
            width: 100%;
            border-collapse: collapse;
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
            font-style: normal;
            text-align: center;
            color: #555;
            margin-top: 2px;
        }
        .title {
            margin: 22px 0 30px;
            text-align: center;
            font-size: 24px;
            font-style: normal;
            font-weight: 700;
            text-decoration: underline;
        }
        .line {
            margin: 5px 0;
        }
        .bullet {
            display: inline-block;
            width: 28px;
            font-style: normal;
        }
        .label {
            display: inline-block;
            min-width: 220px;
        }
        .value {
            font-weight: 700;
        }
        .date-place {
            margin-top: 16px;
            text-align: right;
            font-weight: 700;
            font-style: normal;
        }
        .signature {
            margin-top: 28px;
            text-align: center;
        }
        .signature div {
            margin-top: 12px;
        }
        .doc-footer {
            position: fixed;
            left: 10mm;
            right: 10mm;
            bottom: 1mm;
            border-top: 1px solid #777;
            padding-top: 3px;
            font-size: 9px;
            font-style: normal;
            color: #444;
        }
    </style>
</head>
<body>
@php
    $unit = $motorcycleUnit;
    $model = $unit?->motorcycleModel;
    $homologation = $model?->homologation;
    $companyName = $company->name;
    $brandRecord = $model?->brand;
    $brand = $brandRecord?->name ?: $model?->marque;
    $constructor = $model?->usine_fabrication ?: $homologation?->manufacturer;
    $accreditationReference = $brandRecord?->accreditation_reference ?: $model?->titre_homologation ?: $homologation?->homologation_number;
    $homologationNumber = $model?->titre_homologation ?: $homologation?->homologation_number;
    $homologationDate = $model?->date_homologation?->format('d/m/Y')
        ?: $homologation?->homologation_date?->format('d/m/Y');
    $isResellerSale = filled($document->sale?->reseller_id);
    $clientType = $isResellerSale ? null : $client?->client_type;
    $clientName = $isResellerSale ? '' : match ($clientType) {
        'company' => $client?->company_name,
        'administration' => $client?->administration_name,
        default => $client?->display_name,
    };
    $clientIdentity = $isResellerSale ? '' : ($clientType === 'company'
        ? $client?->rc
        : ($clientType === 'administration' ? null : ($client?->rc ?: $client?->cin)));
    $capacity = $model?->cylindree ? $model->cylindree . ' CC' : null;
    $power = trim($capacity . ($model?->puissance_effective ? ' / ' . $model->puissance_effective : ''));
    $city = strtoupper(\Illuminate\Support\Str::ascii($company->city ?: 'SALE'));
@endphp

    <div class="background">{{ strtoupper($brand ?? '') }}</div>

    <div class="doc-header">
        <table>
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
    </div>

    <div class="title">{{ __('messages.conformity_certificate_title') }}</div>

    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_intro', ['company' => $companyName, 'brand' => $brand]) }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_constructor') }}: <span class="value">{{ $constructor }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_mandataire', ['company' => $companyName]) }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ $accreditationReference }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_vehicle_intro') }}</div>

    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.marque') }}</span>: <span class="value">{{ $brand }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.genre') }}</span>: <span class="value">{{ $model?->genre }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.model') }}</span>: <span class="value">{{ $model?->modele }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.type') }}</span>: <span class="value">{{ $model?->type }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.conformity_category_label') }}</span>: <span class="value">{{ $model?->categorie }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.conformity_chassis_label') }}</span>: <span class="value">{{ $unit?->chassis_number }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.conformity_engine_power') }}</span>: <span class="value">{{ $power }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.fuel') }}</span>: <span class="value">{{ $model?->carburant }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="label">{{ __('messages.conformity_cylinder_label') }}</span>: <span class="value">{{ $model?->nombre_cylindres }}</span></div>

    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_type_sentence') }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_homologation_sentence', ['number' => $homologationNumber, 'date' => $homologationDate]) }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_client_name') }} : <span class="value">{{ $clientName }}</span></div>
    @if($clientType !== 'administration')
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_client_identity') }} : <span class="value">{{ $clientIdentity }}</span></div>
    @endif
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.address') }} : <span class="value">{{ $isResellerSale ? '' : $client?->address }}</span></div>

    <div class="date-place">{{ __('messages.conformity_done_at', ['city' => $city, 'date' => $document->document_date?->format('d/m/Y')]) }}</div>

    <div class="signature">
        <div>{{ __('messages.conformity_signature_constructor') }}</div>
        <div>{{ __('messages.conformity_signature_mandataire') }}</div>
    </div>

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
