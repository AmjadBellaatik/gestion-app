@extends('documents.pdf.layouts.master')

@push('styles')
{{-- Invoice-specific overrides (header row colors, special sections) --}}
<style>
    .items-table th  { background: #1f2937; }
    .totals tr.grand td { background: #f3f4f6; }
</style>
@endpush

@section('content')
@php
    $companyName = $company->name;
    $buyer = $document->reseller ?: $document->sale?->reseller ?: $client;
    $isResellerBuyer = $buyer instanceof \App\Models\Reseller;
    $clientType  = $client?->client_type ?: 'person';
    $clientName  = match ($clientType) {
        'company'        => $client?->company_name,
        'administration' => $client?->administration_name,
        default          => $client?->display_name,
    };
    $buyerName = $isResellerBuyer ? $buyer?->name : $clientName;
    $purchaseOrderNumber = data_get($document->metadata, 'purchase_order_number')
        ?: $document->sale?->purchase_order_number;

    $saleDiscount = $document->sale ? max(0.0, (float) $document->sale->discount) : 0.0;
    $discount     = max($saleDiscount, (float) $document->discount_amount);
    $discountNote = $document->sale?->discount_note;

    $totalTtc = (float) $document->total_amount;
    if ($totalTtc <= 0 && $document->items->isNotEmpty()) {
        $totalTtc = (float) $document->items->sum(fn ($item) => (float) $item->total);
    }
    if ($totalTtc <= 0 && $document->sale) {
        $totalTtc = (float) ($document->sale->total ?: $document->sale->paid_amount);
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

    {{-- ── Company header (page 1 only — normal flow) ──────────────────────── --}}
    <table class="doc-header">
        <tr>
            <td style="width: 75%; text-align: center; vertical-align: middle;">
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

    <div class="doc-title">{{ __('messages.invoice') }}</div>
    <div class="doc-ref">
        {{ __('messages.document_number') }} : {{ $document->document_number }}
        &nbsp;|&nbsp;
        {{ __('messages.document_date') }} : {{ $document->document_date?->format('d/m/Y') }}
        @if($purchaseOrderNumber)
            &nbsp;|&nbsp; {{ __('messages.purchase_order') }} : {{ $purchaseOrderNumber }}
        @endif
    </div>

    <table class="info-table" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.client') }}</div>
                    <strong>{{ $buyerName }}</strong><br>
                    @if($isResellerBuyer)
                        @if($buyer?->ice){{ __('messages.ice') }}: {{ $buyer->ice }}<br>@endif
                        @if($buyer?->phone){{ __('messages.phone') }}: {{ $buyer->phone }}<br>@endif
                    @elseif(in_array($clientType, ['company', 'administration']))
                        @if($client?->ice){{ __('messages.ice') }}: {{ $client->ice }}<br>@endif
                        @if($client?->phone){{ __('messages.phone') }}: {{ $client->phone }}<br>@endif
                    @else
                        @if($client?->cin)CIN: {{ $client->cin }}<br>@endif
                    @endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">{{ __('messages.payment_information') }}</div>
                    @if($purchaseOrderNumber)
                        <strong>{{ __('messages.purchase_order') }} : {{ $purchaseOrderNumber }}</strong><br>
                    @endif
                    @if($company->bank_name || $company->rib)
                        {{ __('messages.bank_details') }}: {{ $company->bank_name }} {{ $company->rib }}
                    @endif
                    @if($document->sale?->sale_number)
                        <br>{{ __('messages.sale') }}: {{ $document->sale->sale_number }}
                    @endif
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

    {{-- ── Closing block: totals + total-in-words + signature ──────────────────
         pdf-protect keeps this entire block on a single page.
         If insufficient space remains, DOMPDF pushes the block to the
         next page before rendering it — never splits it into the footer.
    ────────────────────────────────────────────────────────────────────────── --}}
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

        <div class="total-words">
            Arrêté la présente Facture à la somme TTC de :
            <strong>{{ \App\Services\Amounts\AmountInWordsService::convert($totalTtc, 'fr') }}</strong>
        </div>

        <table class="signatures">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%;"><div class="signature-line">{{ __('messages.company_signature') }}</div></td>
            </tr>
        </table>
    </div>
@endsection
