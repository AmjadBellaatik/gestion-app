@extends('documents.pdf.layouts.master')

@push('styles')
<style>
    body { font-size: 14px; font-style: italic; line-height: 1.33; }

    /* Watermark variant — full-page coverage */
    .pdf-watermark { position: fixed; top: 41%; font-size: 58px; font-style: normal; }

    /* Document header override for conformity style */
    .doc-header { font-style: normal; }

    /* Bullet lines */
    .line  { margin: 5px 0; }
    .bullet { display: inline-block; width: 28px; font-style: normal; }
    .c-label { display: inline-block; min-width: 220px; }
    .c-value  { font-weight: 700; }

    /* Date and signature */
    .date-place { margin-top: 16px; text-align: right; font-weight: 700; font-style: normal; }
    .conformity-signature { margin-top: 28px; text-align: center; font-style: normal; }
    .conformity-signature div { margin-top: 12px; }
</style>
@endpush

@section('content')
@php
    $unit = $motorcycleUnit;
    $model = $unit?->motorcycleModel;
    $homologation = $model?->homologation;
    $companyName  = $company->name;
    $brandRecord  = $model?->brand;
    $brand        = $brandRecord?->name ?: $model?->marque;
    $constructor  = $model?->usine_fabrication ?: $homologation?->manufacturer;
    $accreditationReference = $brandRecord?->accreditation_reference ?: $model?->titre_homologation ?: $homologation?->homologation_number;
    $homologationNumber = $model?->titre_homologation ?: $homologation?->homologation_number;
    $homologationDate   = $model?->date_homologation?->format('d/m/Y')
        ?: $homologation?->homologation_date?->format('d/m/Y');
    $isResellerSale = filled($document->sale?->reseller_id);
    $clientType = $isResellerSale ? null : $client?->client_type;
    $clientName = $isResellerSale ? '' : match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => $client?->display_name,
    };
    $clientIdentity = $isResellerSale ? '' : ($clientType === 'company'
        ? $client?->rc
        : ($clientType === 'administration' ? null : ($client?->rc ?: $client?->cin)));
    $capacity = $model?->cylindree ? $model->cylindree . ' cm³' : null;
    $city = strtoupper(\Illuminate\Support\Str::ascii($company->city ?: 'SALE'));
@endphp

    <div class="pdf-watermark">{{ strtoupper($brand ?? '') }}</div>

    <div class="doc-header" style="margin-bottom: 28px; font-style: normal;">
        <table style="width:100%; border-collapse:collapse;">
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

    <div style="margin: 22px 0 30px; text-align: center; font-size: 24px; font-style: normal; font-weight: 700; text-decoration: underline;">
        {{ __('messages.conformity_certificate_title') }}
    </div>

    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_intro', ['company' => $companyName, 'brand' => $brand]) }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_constructor') }}: <span class="c-value">{{ $constructor }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_mandataire', ['company' => $companyName]) }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ $accreditationReference }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_vehicle_intro') }}</div>

    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.marque') }}</span>: <span class="c-value">{{ $brand }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.genre') }}</span>: <span class="c-value">{{ $model?->genre }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.model') }}</span>: <span class="c-value">{{ $model?->modele }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.type') }}</span>: <span class="c-value">{{ $model?->type }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.conformity_category_label') }}</span>: <span class="c-value">{{ $model?->categorie }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.conformity_chassis_label') }}</span>: <span class="c-value">{{ $unit?->chassis_number }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.engine_capacity') }}</span>: <span class="c-value">{{ $capacity }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.fuel') }}</span>: <span class="c-value">{{ $model?->carburant }}</span></div>
    <div class="line"><span class="bullet">&#10146;</span><span class="c-label">{{ __('messages.conformity_cylinder_label') }}</span>: <span class="c-value">{{ $model?->nombre_cylindres }}</span></div>

    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_type_sentence') }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_homologation_sentence', ['number' => $homologationNumber, 'date' => $homologationDate]) }}</div>
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_client_name') }} : <span class="c-value">{{ $clientName }}</span></div>
    @if($clientType !== 'administration')
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.conformity_client_identity') }} : <span class="c-value">{{ $clientIdentity }}</span></div>
    @endif
    <div class="line"><span class="bullet">&#10146;</span>{{ __('messages.address') }} : <span class="c-value">{{ $isResellerSale ? '' : $client?->address }}</span></div>

    <div class="pdf-protect">
        <div class="date-place">{{ __('messages.conformity_done_at', ['city' => $city, 'date' => $document->document_date?->format('d/m/Y')]) }}</div>
        <div class="conformity-signature">
            <div>{{ __('messages.conformity_signature_constructor') }}</div>
            <div>{{ __('messages.conformity_signature_mandataire') }}</div>
        </div>
    </div>
@endsection
