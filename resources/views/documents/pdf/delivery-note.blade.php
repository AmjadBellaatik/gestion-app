@extends('documents.pdf.layouts.master')

@section('content')
@php
    $companyName = $company->name;
    $buyer = $document->reseller ?: $document->sale?->reseller ?: $client;
    $isResellerBuyer = $buyer instanceof \App\Models\Reseller;
    $clientType  = $client?->client_type;
    $clientName  = match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => trim(($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')),
    };
    $buyerName = $isResellerBuyer ? $buyer?->name : $clientName;

    $repairTicket = $document->repairTicket;
    // Use the document-level discount (already factored into total_amount by recalculateTotals).
    // For sale-linked BLs fall back to the sale discount when the document discount was not set.
    $discount = (float) $document->discount_amount > 0
        ? max(0.0, (float) $document->discount_amount)
        : ($document->sale ? max(0.0, (float) $document->sale->discount) : 0.0);
    $discountNote = $document->sale?->discount_note;

    $totalTtc = (float) $document->total_amount;
    if ($totalTtc <= 0 && $document->items->isNotEmpty()) {
        $totalTtc = (float) $document->items->sum(fn ($item) => (float) $item->total);
    }
    $grossTtc  = $discount > 0 ? round($totalTtc + $discount, 2) : $totalTtc;
    $taxAmount = $totalTtc > 0
        ? round($totalTtc * (20 / 120), 2)
        : (float) $document->tax_amount;
    $subtotal = $totalTtc > 0
        ? round($totalTtc - $taxAmount, 2)
        : (float) $document->subtotal;

    // Chassis number + color — only present for electric scooters / bicycles.
    $chassisNumber = $document->sale?->chassis_number;
    $vehicleColor  = $document->sale?->color;

@endphp

    <div class="pdf-watermark">{{ strtoupper($companyName) }}</div>

    @include('documents.pdf.partials.doc-header')

    <div class="doc-title">{{ __('messages.delivery_note') }}</div>

    <div style="margin-bottom: 4px; font-size: 11px;"><span style="font-weight:700;">{{ __('messages.document_number') }} :</span> {{ $document->document_number }}</div>
    <div style="margin-bottom: 4px; font-size: 11px;"><span style="font-weight:700;">{{ __('messages.document_date') }} :</span> {{ $document->document_date?->format('d/m/Y') }}</div>
    @if($document->sale)
    <div style="margin-bottom: 4px; font-size: 11px;"><span style="font-weight:700;">{{ __('messages.sale') }} :</span> {{ $document->sale->sale_number }}</div>
    @elseif($repairTicket)
    <div style="margin-bottom: 4px; font-size: 11px;"><span style="font-weight:700;">{{ __('messages.repair_ticket') }} :</span> {{ $repairTicket->ticket_number }}</div>
    @endif

    <div style="border: 1px solid #555; padding: 8px 10px; margin-top: 12px; margin-bottom: 14px;">
        <div style="font-weight:700; text-transform:uppercase; margin-bottom:4px; font-size:11px;">{{ __('messages.client') }}</div>
        <div><strong>{{ $buyerName }}</strong></div>
        @if($isResellerBuyer)
            @if($buyer?->ice)<div>{{ __('messages.ice') }}: {{ $buyer->ice }}</div>@endif
            @if($buyer?->phone)<div>{{ __('messages.phone') }}: {{ $buyer->phone }}</div>@endif
        @elseif(in_array($clientType, ['company', 'administration']))
            @if($client?->ice)<div>{{ __('messages.ice') }}: {{ $client->ice }}</div>@endif
            @if($client?->phone)<div>{{ __('messages.phone') }}: {{ $client->phone }}</div>@endif
        @else
            @if($client?->cin)<div>CIN: {{ $client->cin }}</div>@endif
        @endif
    </div>

    @if($chassisNumber || $vehicleColor)
    <div style="margin-bottom: 14px; font-size: 11px;">
        @if($chassisNumber)<div><strong>{{ __('messages.chassis_number') }} :</strong> {{ $chassisNumber }}</div>@endif
        @if($vehicleColor)<div><strong>{{ __('messages.color') }} :</strong> {{ $vehicleColor }}</div>@endif
    </div>
    @endif

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

        <table class="signatures signature-section">
            <tr>
                <td><div class="signature-line">{{ __('messages.delivered_by') }}</div></td>
                <td><div class="signature-line">{{ __('messages.received_by') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
