@php
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

<table class="items">
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
        @foreach ($document->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->description }}</strong>
                    @if ($item->motorcycleUnit && $document->documentType?->code !== \App\Models\DocumentType::QUOTATION)
                        <br>
                        <span class="small">
                            {{ __('messages.chassis_number') }}: {{ $item->motorcycleUnit->chassis_number }}
                        </span>
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
