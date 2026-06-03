@extends('pdf.layouts.master')
@php $documentTitle = __('messages.warranty_report'); @endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_warranties') }}</strong><br>{{ $warranties->count() }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.active') }}</strong><br>{{ $activeCount }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.expired') }}</strong><br>{{ $expiredCount }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.expiring_soon') }}</strong><br>{{ $expiringSoon }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('messages.warranties_list') }}</div>
    @if($warranties->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.item') }}</th>
                <th>{{ __('messages.client') }}</th>
                <th>{{ __('messages.start_date') }}</th>
                <th>{{ __('messages.end_date') }}</th>
                <th>{{ __('messages.km_limit') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($warranties as $warranty)
            @php
                $itemLabel = $warranty->motorcycleUnit
                    ? ($warranty->motorcycleUnit->motorcycleModel?->brand.' '.$warranty->motorcycleUnit->motorcycleModel?->model.' — '.$warranty->motorcycleUnit->chassis_number)
                    : ($warranty->product?->name ?? '—');
            @endphp
            <tr>
                <td class="bold">{{ $itemLabel }}</td>
                <td>{{ $warranty->client?->display_name ?? '—' }}</td>
                <td>{{ $warranty->start_date ? $warranty->start_date->format('d/m/Y') : '—' }}</td>
                <td>{{ $warranty->end_date ? $warranty->end_date->format('d/m/Y') : '—' }}</td>
                <td>{{ $warranty->km_limit ? number_format($warranty->km_limit).' km' : '—' }}</td>
                <td>{{ __('messages.'.$warranty->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
