@extends('documents.pdf.layouts.master')

@push('styles')
<style>
    body { font-size: 13px; line-height: 1.48; }

    .vehicle-table { width: 100%; border-collapse: collapse; }
    .vehicle-table td { padding: 7px 9px; border: 1px solid #444; vertical-align: top; }
    .v-label { width: 28%; font-weight: 700; background: #f3f4f6; }
    .v-value { font-weight: 700; }

    .party { margin-top: 16px; padding: 10px 12px; border: 1px solid #555; }
    .party-title { margin-bottom: 4px; font-weight: 700; text-transform: uppercase; }

    /* Long warranty text: allow natural breaks between paragraphs */
    .terms { margin-top: 12px; text-align: justify; }
    .terms p { page-break-inside: avoid; }

    .date-place { margin-top: 12px; text-align: right; font-weight: 700; }

    /* Signature block: stays on the same page as the date line.
       If there is not enough room, DOMPDF pushes the whole block to the next page. */
    .warranty-sign-block {
        page-break-inside: avoid;
    }
    .warranty-sign-block .signatures {
        margin-top: 6px;
    }
    .warranty-sign-block .signature-line {
        margin: 30px 28px 0;
    }
</style>
@endpush

@section('content')
@php
    $unit = $motorcycleUnit
        ?: $document->items->first(fn ($item) => $item->motorcycleUnit)?->motorcycleUnit
        ?: $document->primaryMotorcycleUnit();
    $model   = $unit?->motorcycleModel;
    $product = $document->items->first(fn ($item) => $item->product)?->product;
    $coveredItemName = $unit
        ? trim(($model?->marque ? $model->marque . ' ' : '') . ($model?->modele ?: ''))
        : $product?->name;
    $coveredItemType      = $unit ? $model?->type : ($product?->type ? __('messages.' . $product->type) : null);
    $coveredItemReference = $unit ? $unit?->chassis_number : ($product?->sku ?: $product?->barcode);
    $companyName          = $company->name;
    $clientType           = $client?->client_type;
    $clientName           = match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => trim(($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')),
    };
    $clientIdentity = $clientType === 'company'
        ? $client?->rc
        : ($clientType === 'administration' ? null : ($client?->rc ?: $client?->cin));
    $warrantyDurationValue = data_get($document->metadata, 'warranty_duration_value')
        ?: data_get($document->metadata, 'warranty_years');
    $warrantyDurationUnit  = data_get($document->metadata, 'warranty_duration_unit', 'years');
    $warrantyDurationLabel = trim($warrantyDurationValue . ' ' . __('messages.' . $warrantyDurationUnit));
    $warrantyKilometers    = data_get($document->metadata, 'warranty_kilometers');
    $city  = \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($company->city ?: 'Sale'));
    $brand = $model?->brand?->name ?: $model?->marque;
    $isElectricOrScooter = !$unit && $product && in_array($product->type, ['trotinette', 'velo_electrique']);
@endphp

    <div class="pdf-watermark">{{ strtoupper($companyName) }}</div>

    @include('documents.pdf.partials.doc-header')

    <div class="doc-title">Contrat de garantie</div>

    <p style="margin-bottom: 16px; text-align: justify;">
        Ce contrat presente et explique les pieces de garantie du vehicule. Il est etabli entre les deux parties
        designees ci-dessous et concerne le vehicule identifie par les informations suivantes :
    </p>

    <table class="vehicle-table">
        @if($unit)
            <tr>
                <td class="v-label">MARQUE</td>
                <td class="v-value">{{ $model?->marque }}</td>
                <td class="v-label">Modele</td>
                <td class="v-value">{{ $model?->modele }}</td>
            </tr>
            <tr>
                <td class="v-label">No Chassis</td>
                <td class="v-value">{{ $unit?->chassis_number }}</td>
                <td class="v-label">Type</td>
                <td class="v-value">{{ $model?->type }}</td>
            </tr>
        @else
            <tr>
                <td class="v-label">{{ $coveredItemType }}</td>
                <td class="v-value">{{ $coveredItemName }}</td>
                <td class="v-label">{{ __('messages.type') }}</td>
                <td class="v-value">{{ $coveredItemType }}</td>
            </tr>
            <tr>
                <td class="v-label">{{ __('messages.sku') }} / {{ __('messages.barcode') }}</td>
                <td class="v-value" colspan="3">{{ $coveredItemReference }}</td>
            </tr>
        @endif
    </table>

    <div class="party">
        <div class="party-title">D'une part</div>
        <div>SOCIETE <strong>{{ $companyName }}</strong></div>
        <div>{{ $company->address ?: $company->legal_address }}</div>
    </div>

    <div class="party">
        <div class="party-title">D'autre part</div>
        <div><strong>{{ $clientName }}</strong></div>
        @if($clientIdentity)
            <div>{{ __('messages.cin') }} / {{ __('messages.rc') }} : {{ $clientIdentity }}</div>
        @endif
        <div>{{ $client?->address }}</div>
    </div>

    <div class="terms">
        @if($isElectricOrScooter)
            <p>Il a été convenu et arrêté ce qui suit :</p>
            <p>
                SOCIETE <strong>{{ $companyName }}</strong> s'engage à accorder une garantie de
                <strong>{{ $warrantyDurationLabel }}</strong>@if($warrantyKilometers) ou <strong>{{ $warrantyKilometers }} KM</strong>@endif
                sur le moteur, le contrôleur, la batterie ainsi que les composants électriques d'origine
                du véhicule à compter de la date de livraison.
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
                @if($warrantyKilometers) ou <strong>{{ $warrantyKilometers }} KM</strong>@endif
                a compter de la date de livraison (par exemple : cylindre, vilebrequin...).
            </p>
            <p>
                Cette garantie ne contient pas les frais de la main d'oeuvre, les cables et l'equipement electronique,
                plaquettes de frein, pneumatique et les articles fournis sur demande speciale de l'acheteur, ni la tenue
                de la peinture, du nickel ou du chrome.
            </p>
        @endif
    </div>

    <div class="warranty-sign-block signature-section">
        <div class="date-place">{{ $city }} le : {{ $document->document_date?->format('d/m/Y') }}</div>
        <table class="signatures">
            <tr>
                <td><div class="signature-line">{{ __('messages.client_signature') }}</div></td>
                <td><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
