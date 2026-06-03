@extends('pdf.layouts.master')
@php
    $documentTitle = __('messages.stock_report');
    $fmt = fn(float $v) => number_format($v, 2, '.', ' ');
@endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_products') }}</strong><br>{{ $totalProducts }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.low_stock') }}</strong><br>{{ $lowStockCount }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.stock_valuation') }}</strong><br>MAD {{ $fmt($totalValue) }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.movements_in_period') }}</strong><br>{{ $movementsCount }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('messages.products_inventory') }}</div>
    @if($products->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.product') }}</th>
                <th>{{ __('messages.sku') }}</th>
                <th>{{ __('messages.current_stock') }}</th>
                <th>{{ __('messages.purchase_price') }}</th>
                <th>{{ __('messages.selling_price') }}</th>
                <th>{{ __('messages.stock_value') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td class="bold">{{ $product->name }}</td>
                <td>{{ $product->sku ?? '—' }}</td>
                <td class="bold">{{ number_format($product->current_qty, 2) }}</td>
                <td>{{ $product->purchase_price ? 'MAD '.$fmt($product->purchase_price) : '—' }}</td>
                <td>MAD {{ $fmt($product->selling_price) }}</td>
                <td class="bold">MAD {{ $fmt($product->stock_value) }}</td>
                <td>{{ $product->is_low ? __('messages.low_stock') : ($product->current_qty <= 0 ? __('messages.out_of_stock') : __('messages.in_stock')) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@if($movements->isNotEmpty())
<div class="section">
    <div class="section-title">{{ __('messages.stock_movements') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.product') }}</th>
                <th>{{ __('messages.type') }}</th>
                <th>{{ __('messages.quantity') }}</th>
                <th>{{ __('messages.unit_cost') }}</th>
                <th>{{ __('messages.reference') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $mv)
            <tr>
                <td>{{ $mv->created_at->format('d/m/Y') }}</td>
                <td>{{ $mv->product?->name ?? '—' }}</td>
                <td>{{ __('messages.'.$mv->type) }}</td>
                <td class="bold">{{ number_format($mv->quantity, 2) }}</td>
                <td>{{ $mv->unit_cost ? 'MAD '.$fmt($mv->unit_cost) : '—' }}</td>
                <td>{{ $mv->reference ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
