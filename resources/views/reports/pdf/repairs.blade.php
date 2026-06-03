@extends('pdf.layouts.master')
@php
    $documentTitle = __('messages.repairs_report');
    $fmt = fn(float $v) => number_format($v, 2, '.', ' ');
@endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_repairs') }}</strong><br>{{ $totalRepairs }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.open') }}</strong><br>{{ $openCount }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.completed') }}</strong><br>{{ $completedCount }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.under_warranty') }}</strong><br>{{ $warrantyCount }}</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.total_revenue') }}</strong><br>MAD {{ $fmt($totalRevenue) }}</td>
            <td style="padding:8px;border:1px solid #ddd;"><strong>{{ __('messages.avg_repair_time') }}</strong><br>{{ $avgTime }}h</td>
            <td style="padding:8px;border:1px solid #ddd;" colspan="2"></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('messages.repairs_list') }}</div>
    @if($repairs->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.ticket') }}</th>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.client') }}</th>
                <th>{{ __('messages.motorcycle') }}</th>
                <th>{{ __('messages.technician') }}</th>
                <th>{{ __('messages.total_cost') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($repairs as $repair)
            <tr>
                <td class="bold">{{ $repair->ticket_number ?? '#'.$repair->id }}</td>
                <td>{{ $repair->created_at->format('d/m/Y') }}</td>
                <td>{{ $repair->client?->display_name ?? '—' }}</td>
                <td>{{ $repair->motorcycleUnit?->motorcycleModel?->brand }} {{ $repair->motorcycleUnit?->motorcycleModel?->model ?? '—' }}</td>
                <td>{{ $repair->technician?->name ?? '—' }}</td>
                <td>{{ $repair->total_cost > 0 ? 'MAD '.$fmt($repair->total_cost) : '—' }}</td>
                <td>{{ __('messages.'.$repair->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
