@extends('documents.pdf.layouts.master')

@push('styles')
<style>
    .repair-banner { background: #1f2937; color: #fff; padding: 6px 10px; font-size: 11px; font-weight: 700; margin: 14px 0 4px; text-transform: uppercase; }
</style>
@endpush

@section('content')
@php
    $companyName = $company->name;
    $clientType  = $client?->client_type ?: 'person';
    $clientName  = match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => $client?->display_name,
    };

    $repairTicket = $document->repairTicket;
    $repairNumber = $repairTicket?->ticket_number;
    $repairUnit   = $motorcycleUnit ?? $document->primaryMotorcycleUnit();
    $repairModel  = $repairUnit?->motorcycleModel;

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

    $watermarkText = strtoupper($repairModel?->brand?->name ?: $repairModel?->marque ?: $companyName);
@endphp

    <div class="pdf-watermark">{{ $watermarkText }}</div>

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

    <div class="doc-title">{{ __('messages.repair_invoice') }}</div>
    <div class="doc-ref">
        {{ __('messages.document_number') }} : {{ $document->document_number }}
        &nbsp;|&nbsp;
        {{ __('messages.document_date') }} : {{ $document->document_date?->format('d/m/Y') }}
        @if($repairNumber)
            &nbsp;|&nbsp; Réf. réparation : {{ $repairNumber }}
        @endif
    </div>

    <table class="info-table" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.client') }}</div>
                    <strong>{{ $clientName }}</strong><br>
                    @if(in_array($clientType, ['company', 'administration']))
                        @if($client?->ice){{ __('messages.ice') }}: {{ $client->ice }}<br>@endif
                        @if($client?->phone){{ __('messages.phone') }}: {{ $client->phone }}<br>@endif
                    @else
                        @if($client?->cin)CIN: {{ $client->cin }}<br>@endif
                        @if($client?->phone){{ __('messages.phone') }}: {{ $client->phone }}<br>@endif
                    @endif
                </div>
            </td>
            <td style="width: 50%;">
                @if($repairUnit)
                <div class="box">
                    <div class="box-title">{{ __('messages.motorcycle') }}</div>
                    <strong>{{ $repairModel?->marque }} {{ $repairModel?->modele }}</strong><br>
                    {{ __('messages.chassis_number') }}: {{ $repairUnit->chassis_number }}<br>
                    @if($repairModel?->type){{ __('messages.type') }}: {{ $repairModel->type }}@endif
                </div>
                @endif
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

    <div class="pdf-protect">
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
            Arrêté la présente Facture de Réparation à la somme TTC de :
            <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
        </div>

        @if($document->notes)
        <div style="margin-top: 12px; padding: 8px 10px; border: 1px solid #d1d5db; font-size: 11px;">
            <div style="font-weight:700; text-transform:uppercase; margin-bottom:4px; font-size:10px;">{{ __('messages.notes') }}</div>
            {{ $document->notes }}
        </div>
        @endif

        <table class="signatures">
            <tr>
                <td><div class="signature-line">{{ __('messages.client_signature') }}</div></td>
                <td><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
