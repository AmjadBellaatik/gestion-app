@extends('pdf.layouts.master')
@php
    $documentTitle = __('messages.resellers_report');
    $fmt = fn(float $v) => number_format($v, 2, '.', ' ');
@endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_resellers') }}</strong><br>{{ $totalResellers }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_orders') }}</strong><br>{{ $totalOrders }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_paid') }}</strong><br>MAD {{ $fmt($totalPaid) }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_debt') }}</strong><br>MAD {{ $fmt($totalDebt) }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('messages.resellers_list') }}</div>
    @if($resellers->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.phone') }}</th>
                <th>{{ __('messages.orders') }}</th>
                <th>{{ __('messages.total_paid') }}</th>
                <th>{{ __('messages.current_debt') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resellers as $reseller)
            <tr>
                <td class="bold">{{ $reseller->name }}</td>
                <td>{{ $reseller->phone ?? '—' }}</td>
                <td>{{ $reseller->total_orders }}</td>
                <td>MAD {{ $fmt($reseller->total_paid ?? 0) }}</td>
                <td>{{ $reseller->current_debt > 0 ? 'MAD '.$fmt($reseller->current_debt) : __('messages.settled') }}</td>
                <td>{{ $reseller->is_blocked ? __('messages.blocked') : ($reseller->is_active ? __('messages.active') : __('messages.inactive')) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
