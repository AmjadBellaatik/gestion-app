@extends('documents.pdf.layouts.master')

@push('styles')
<style>
    /* ── Motorcycle technical spec table ──────────────────────────────────── */
    .tech-title {
        margin-top: 18px;
        padding: 7px 9px;
        background: #1f2937;
        color: #fff;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        page-break-after: avoid;  /* keep title with its table */
    }
    .tech-table { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .tech-table td { border: 1px solid #d1d5db; padding: 5px 7px; vertical-align: top; font-size: 11px; }
    .tech-table td.label { width: 23%; background: #f3f4f6; font-weight: 700; }
</style>
@endpush

@section('content')
@php
    $companyName = $company->name;

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

@endphp

    <div class="pdf-watermark">{{ strtoupper($companyName) }}</div>

    @include('documents.pdf.partials.doc-header')

    <div class="doc-title">{{ __('messages.quotation') }}</div>
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
                <th class="num" style="width:28px;">N°</th>
                <th>{{ __('messages.description') }}</th>
                <th class="num">{{ __('messages.quantity') }}</th>
                <th class="num">{{ __('messages.unit_price_ht') }}</th>
                <th class="num">{{ __('messages.tax_rate') }}</th>
                <th class="num">{{ __('messages.total_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
            <tr>
                <td class="num">{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $item->description }}</strong>
                    @if($item->motorcycleUnit)
                        <br><span class="chassis">{{ __('messages.chassis_number') }}: {{ $item->motorcycleUnit->chassis_number }}</span>
                    @endif
                </td>
                <td class="num">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                <td class="num">{{ number_format((float) $item->unit_price / (1 + ((float) $item->tax_rate) / 100), 2, ',', ' ') }} MAD</td>
                <td class="num">20%</td>
                <td class="num">{{ number_format((float) $item->total, 2, ',', ' ') }} MAD</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Financial summary — protected as a unit --}}
    <div class="pdf-protect">
        <table class="totals totals-section">
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
        <div class="total-words comments-section">
            Arrêté le présent Devis à la somme TTC de :
            <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
        </div>
    </div>

    {{-- Motorcycle technical specs — each unit is individually protected --}}
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

    {{-- Signature — protected separately after tech specs --}}
    <div class="signature-section">
        <table class="signatures">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%;"><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
