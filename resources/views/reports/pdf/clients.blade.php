@extends('pdf.layouts.master')
@php
    $documentTitle = __('messages.clients_report');
    $fmt = fn(float $v) => number_format($v, 2, '.', ' ');
@endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_clients') }}</strong><br>{{ $totalClients }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.active') }}</strong><br>{{ $activeClients }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.blocked') }}</strong><br>{{ $blockedClients }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.new_in_period') }}</strong><br>{{ $newInPeriod }}</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.person') }}</strong><br>{{ $persons }}</td>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.company') }}</strong><br>{{ $companies }}</td>
            <td style="padding:8px;border:1px solid #ddd;" colspan="2"></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('messages.clients_list') }}</div>
    @if($clients->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.type') }}</th>
                <th>{{ __('messages.phone') }}</th>
                <th>{{ __('messages.total_sales') }}</th>
                <th>{{ __('messages.total_revenue') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
            <tr>
                <td class="bold">{{ $client->display_name }}</td>
                <td>{{ __('messages.'.$client->client_type) }}</td>
                <td>{{ $client->phone ?? '—' }}</td>
                <td>{{ $client->sales_count }}</td>
                <td>MAD {{ $fmt($client->sales_sum_total ?? 0) }}</td>
                <td>{{ $client->is_blocked ? __('messages.blocked') : ($client->is_active ? __('messages.active') : __('messages.inactive')) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
