@extends('pdf.layouts.master')
@php
    $documentTitle = __('messages.payments_report');
    $fmt = fn(float $v) => number_format($v, 2, '.', ' ');
@endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

{{-- KPI Summary --}}
<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_collected') }}</strong><br>MAD {{ $fmt($totalCollected) }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.pending_cheques') }}</strong><br>MAD {{ $fmt($totalPending) }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.cash') }}</strong><br>MAD {{ $fmt($cashTotal) }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.card') }}</strong><br>MAD {{ $fmt($cardTotal) }}</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.cheque') }}</strong><br>MAD {{ $fmt($chequeTotal) }}</td>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.bank_transfer') }}</strong><br>MAD {{ $fmt($transferTotal) }}</td>
            <td style="padding:8px;border:1px solid #ddd;" colspan="2"></td>
        </tr>
    </table>
</div>

{{-- Payments Table --}}
<div class="section">
    <div class="section-title">{{ __('messages.payments_list') }}</div>
    @if($payments->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.sale') }}</th>
                <th>{{ __('messages.client') }} / {{ __('messages.reseller') }}</th>
                <th>{{ __('messages.payment_method') }}</th>
                <th>{{ __('messages.amount') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                <td class="bold">{{ $payment->sale?->sale_number ?? '—' }}</td>
                <td>{{ $payment->sale?->client?->display_name ?? $payment->sale?->reseller?->name ?? '—' }}</td>
                <td>{{ __('messages.'.$payment->payment_method) }}</td>
                <td class="bold">MAD {{ $fmt($payment->amount) }}</td>
                <td>{{ __('messages.'.$payment->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
