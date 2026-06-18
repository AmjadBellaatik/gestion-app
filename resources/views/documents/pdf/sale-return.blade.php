@extends('documents.pdf.layouts.master')

@push('styles')
<style>
    .reason-box { border: 1px solid #555; padding: 8px 10px; margin-top: 18px; }
    .reason-title { font-weight: 700; text-transform: uppercase; margin-bottom: 4px; font-size: 11px; }
</style>
@endpush

@section('content')
@php
    $companyName = $company->name;
    $clientType  = $client?->client_type;
    $clientName  = match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => trim(($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')),
    };

    $discount     = $document->sale ? max(0.0, (float) $document->sale->discount) : 0.0;
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

@endphp

    <div class="pdf-watermark">{{ strtoupper($companyName) }}</div>

    @include('documents.pdf.partials.doc-header')

    <div class="doc-title">{{ __('messages.sale_return') }}</div>
    <div style="margin-bottom: 4px; font-size: 11px;"><span style="font-weight:700;">{{ __('messages.document_number') }} :</span> {{ $document->document_number }}</div>
    <div style="margin-bottom: 4px; font-size: 11px;"><span style="font-weight:700;">{{ __('messages.document_date') }} :</span> {{ $document->document_date?->format('d/m/Y') }}</div>
    @if($document->sale)
    <div style="margin-bottom: 4px; font-size: 11px;"><span style="font-weight:700;">{{ __('messages.sale') }} :</span> {{ $document->sale->sale_number }}</div>
    @endif

    <table class="info-table" style="margin-top: 12px; margin-bottom: 14px;">
        <tr>
            <td style="width: 50%; padding-right: 10px;">
                <div class="box">
                    <div class="box-title">{{ __('messages.client') }}</div>
                    <div><strong>{{ $clientName }}</strong></div>
                    @if(in_array($clientType, ['company', 'administration']))
                        @if($client?->ice)<div>{{ __('messages.ice') }}: {{ $client->ice }}</div>@endif
                        @if($client?->phone)<div>{{ __('messages.phone') }}: {{ $client->phone }}</div>@endif
                    @else
                        @if($client?->cin)<div>CIN: {{ $client->cin }}</div>@endif
                    @endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.sale') }}</div>
                    @if($document->sale)
                    <div><strong>{{ $document->sale->sale_number }}</strong></div>
                    @endif
                    <div>{{ __('messages.document_number') }}: {{ $document->document_number }}</div>
                    <div>{{ __('messages.document_date') }}: {{ $document->document_date?->format('d/m/Y') }}</div>
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

    <div class="totals-section">
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
    </div>

    {{-- Reason box: can contain long text — natural page flow allowed --}}
    <div class="reason-box comments-section">
        <div class="reason-title">{{ __('messages.return_reason') }}</div>
        {{ $document->notes ?: __('messages.return_document') }}
    </div>

    <div class="signature-section">
        <table class="signatures">
            <tr>
                <td><div class="signature-line">{{ __('messages.client_signature') }}</div></td>
                <td><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
