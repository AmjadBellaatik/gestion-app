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

    // DocumentService synchronizes all totals from the repair ticket into the document.
    // Use stored values directly — no view-layer recalculation.
    $subtotal     = (float) $document->subtotal;
    $taxAmount    = (float) $document->tax_amount;
    $totalTtc     = (float) $document->total_amount;
    $discount     = (float) $document->discount_amount;
    $discountNote = $repairTicket?->discount_note ?? null;
@endphp

    <div class="pdf-watermark">{{ strtoupper($companyName) }}</div>

    @include('documents.pdf.partials.doc-header')

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
                @elseif($repairTicket?->is_foreign_vehicle)
                <div class="box">
                    <div class="box-title">{{ __('messages.vehicle_information') }}</div>
                    <strong>{{ $repairTicket->foreign_brand }} {{ $repairTicket->foreign_model }}</strong><br>
                    @if($repairTicket->foreign_chassis){{ __('messages.chassis_number') }}: {{ $repairTicket->foreign_chassis }}<br>@endif
                </div>
                @endif
            </td>
        </tr>
    </table>

    @if($document->items->isNotEmpty())
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
    @endif

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
            @if($discount > 0)
            <tr>
                <td style="color:#b45309; font-weight:600;">
                    {{ __('messages.discount_amount') }}
                    @if($discountNote)
                        <br><span style="font-weight:400; font-size:10px;">{{ $discountNote }}</span>
                    @endif
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

        <div class="total-words comments-section">
            Arrêté la présente Facture de Réparation à la somme TTC de :
            <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
        </div>

        @if($document->notes)
        <div class="comments-section" style="margin-top: 12px; padding: 8px 10px; border: 1px solid #d1d5db; font-size: 11px;">
            <div style="font-weight:700; text-transform:uppercase; margin-bottom:4px; font-size:10px;">{{ __('messages.notes') }}</div>
            {{ $document->notes }}
        </div>
        @endif

        <table class="signatures signature-section">
            <tr>
                <td><div class="signature-line">{{ __('messages.client_signature') }}</div></td>
                <td><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
