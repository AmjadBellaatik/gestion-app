@extends('pdf.layouts.master')
@php $documentTitle = __('messages.motorcycles_report'); @endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_units') }}</strong><br>{{ $totalUnits }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.in_stock') }}</strong><br>{{ $inStock }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.on_hold') }}</strong><br>{{ $onHold }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.sold') }}</strong><br>{{ $sold }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('messages.motorcycles_list') }}</div>
    @if($units->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.model') }}</th>
                <th>{{ __('messages.chassis_number') }}</th>
                <th>{{ __('messages.engine_number') }}</th>
                <th>{{ __('messages.color') }}</th>
                <th>{{ __('messages.year') }}</th>
                <th>{{ __('messages.client') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($units as $unit)
            <tr>
                <td class="bold">{{ $unit->motorcycleModel?->brand }} {{ $unit->motorcycleModel?->model }}</td>
                <td>{{ $unit->chassis_number ?? '—' }}</td>
                <td>{{ $unit->engine_number ?? '—' }}</td>
                <td>{{ $unit->color ?? '—' }}</td>
                <td>{{ $unit->year ?? '—' }}</td>
                <td>{{ $unit->client?->display_name ?? '—' }}</td>
                <td>{{ __('messages.'.$unit->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
