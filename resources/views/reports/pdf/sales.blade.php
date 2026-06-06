@extends('pdf.layouts.master')
@php
    $documentTitle = __('messages.sales_report');
    $fmt = fn(float $v) => number_format($v, 2, '.', ' ');
@endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

{{-- KPI Summary --}}
<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_sales') }}</strong><br>{{ $sales->count() }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_revenue') }}</strong><br>MAD {{ $fmt($totalRevenue) }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_paid') }}</strong><br>MAD {{ $fmt($totalPaid) }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_unpaid') }}</strong><br>MAD {{ $fmt($totalUnpaid) }}</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.total_discount') }}</strong><br>MAD {{ $fmt($totalDiscount) }}</td>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.average_order') }}</strong><br>MAD {{ $fmt($avgOrder) }}</td>
            <td style="padding:8px;border:1px solid #ddd;" colspan="2"></td>
        </tr>
    </table>
</div>

{{-- Sales Table --}}
<div class="section">
    <div class="section-title">{{ __('messages.sales_list') }}</div>
    @if($sales->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.client') }} / {{ __('messages.reseller') }}</th>
                <th>{{ __('messages.total') }}</th>
                <th>{{ __('messages.paid_amount') }}</th>
                <th>{{ __('messages.remaining_amount') }}</th>
                <th>{{ __('messages.payment_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
                <td class="bold">{{ $sale->sale_number }}</td>
                <td>{{ ($sale->sale_date ?? $sale->created_at)->format('d/m/Y') }}</td>
                <td>{{ $sale->client?->display_name ?? $sale->reseller?->name ?? '—' }}</td>
                <td>MAD {{ $fmt($sale->total) }}</td>
                <td>MAD {{ $fmt($sale->paid_amount) }}</td>
                <td>{{ $sale->remaining_amount > 0 ? 'MAD '.$fmt($sale->remaining_amount) : '—' }}</td>
                <td>{{ __('messages.'.$sale->payment_status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
