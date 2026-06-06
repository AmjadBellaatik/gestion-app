{{--
    Generic fallback document — rendered as a commercial invoice.
    Used when a document type has no specific template configured.

    NOTE: This file formerly used @include('documents.pdf.commercial-invoice').
    Since commercial-invoice now uses @extends(), including it caused Blade
    layout-inheritance conflicts. This file now extends the master directly
    and duplicates the minimal commercial-invoice content.  When a dedicated
    template exists for a document type, this file is never reached.
--}}
@extends('documents.pdf.layouts.master')

@section('content')
@php
    $companyName = $company->name;
    $clientType  = $client?->client_type ?: 'person';
    $clientName  = match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => $client?->display_name,
    };

    $totalTtc = (float) $document->total_amount;
    if ($totalTtc <= 0 && $document->items->isNotEmpty()) {
        $totalTtc = (float) $document->items->sum(fn ($item) => (float) $item->total);
    }
    $taxAmount = $totalTtc > 0 ? round($totalTtc * (20 / 120), 2) : (float) $document->tax_amount;
    $subtotal  = $totalTtc > 0 ? round($totalTtc - $taxAmount, 2) : (float) $document->subtotal;
@endphp

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

    <div class="doc-title">{{ $document->documentType?->name }}</div>
    <div class="doc-ref">
        {{ __('messages.document_number') }} : {{ $document->document_number }}
        &nbsp;|&nbsp;
        {{ __('messages.document_date') }} : {{ $document->document_date?->format('d/m/Y') }}
    </div>

    @if($client)
    <div class="box" style="margin-bottom: 14px;">
        <div class="box-title">{{ __('messages.client') }}</div>
        <strong>{{ $clientName }}</strong>
    </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('messages.description') }}</th>
                <th class="num">{{ __('messages.quantity') }}</th>
                <th class="num">{{ __('messages.unit_price_ttc') }}</th>
                <th class="num">{{ __('messages.total_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
            <tr>
                <td><strong>{{ $item->description }}</strong></td>
                <td class="num">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                <td class="num">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD</td>
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
        <table class="signatures">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%;"><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
