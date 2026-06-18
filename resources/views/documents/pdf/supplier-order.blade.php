@extends('documents.pdf.layouts.master')

@section('content')
@php
    $companyName = $company->name;

    $providerName    = data_get($document->metadata, 'manual_supplier_name');
    $providerPhone   = data_get($document->metadata, 'manual_supplier_phone');
    $providerEmail   = data_get($document->metadata, 'manual_supplier_email');
    $providerAddress = data_get($document->metadata, 'manual_supplier_address');
    $providerIce     = data_get($document->metadata, 'provider_ice');
    $providerRc      = data_get($document->metadata, 'provider_rc');
    $quoteRef        = data_get($document->metadata, 'supplier_quote_number');

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

    $watermarkText = strtoupper($companyName);
@endphp

    <div class="pdf-watermark">{{ $watermarkText }}</div>

    @include('documents.pdf.partials.doc-header')

    <div class="doc-title">{{ __('messages.supplier_order') }}</div>
    <div class="doc-ref">
        {{ __('messages.document_number') }} : {{ $document->document_number }}
        &nbsp;|&nbsp;
        {{ __('messages.document_date') }} : {{ $document->document_date?->format('d/m/Y') }}
        @if($quoteRef)
            &nbsp;|&nbsp; {{ __('messages.provider_quote_reference') }} : {{ $quoteRef }}
        @endif
    </div>

    <table class="info-table" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.provider') }}</div>
                    <strong>{{ $providerName }}</strong><br>
                    @if($providerAddress){{ $providerAddress }}<br>@endif
                    @if($providerPhone){{ __('messages.phone') }}: {{ $providerPhone }}<br>@endif
                    @if($providerEmail){{ __('messages.email') }}: {{ $providerEmail }}<br>@endif
                    @if($providerIce){{ __('messages.ice') }}: {{ $providerIce }}<br>@endif
                    @if($providerRc){{ __('messages.rc') }}: {{ $providerRc }}@endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.delivery_address') }}</div>
                    <strong>{{ $companyName }}</strong><br>
                    {{ $company->address ?: $company->legal_address }}<br>
                    @if($company->city){{ strtoupper($company->city) }}<br>@endif
                    @if($company->phone){{ __('messages.phone') }}: {{ $company->phone }}@endif
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
            {{ __('messages.total_in_letters') }} :
            <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
        </div>

        <table class="signatures signature-section">
            <tr>
                <td><div class="signature-line">{{ __('messages.supplier_signature') }}</div></td>
                <td><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
