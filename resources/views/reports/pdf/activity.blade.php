@extends('pdf.layouts.master')
@php $documentTitle = __('messages.activity_report'); @endphp
@section('content')

<p style="color:#666;font-size:11px;margin-bottom:16px;">{{ __('messages.period') ?? 'Period' }}: <strong>{{ $periodLabel }}</strong></p>

<div class="section">
    <div class="section-title">{{ __('messages.summary') }}</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.total_activities') }}</strong><br>{{ $totalActivities }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.unique_users') }}</strong><br>{{ $uniqueUsers }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.successful_logins') }}</strong><br>{{ $successfulLogins }}</td>
            <td style="padding:8px;border:1px solid #ddd;width:25%;"><strong>{{ __('messages.failed_logins') }}</strong><br>{{ $failedLogins }}</td>
        </tr>
    </table>
</div>

@if($byModule->isNotEmpty())
<div class="section">
    <div class="section-title">{{ __('messages.activity_by_module') }}</div>
    <table style="width:60%;border-collapse:collapse;">
        <thead>
            <tr>
                <th>{{ __('messages.module') }}</th>
                <th class="text-right">{{ __('messages.count') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byModule as $module => $count)
            <tr>
                <td>{{ $module ?: __('messages.unknown') }}</td>
                <td class="bold">{{ $count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="section">
    <div class="section-title">{{ __('messages.activity_log') }}</div>
    @if($activities->isEmpty())
        <p>{{ __('messages.no_data_for_period') }}</p>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.user') }}</th>
                <th>{{ __('messages.module') }}</th>
                <th>{{ __('messages.action') }}</th>
                <th>{{ __('messages.description') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $activity)
            <tr>
                <td style="white-space:nowrap;">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                <td class="bold">{{ $activity->user?->name ?? '—' }}</td>
                <td>{{ $activity->module ?? '—' }}</td>
                <td>{{ $activity->action ?? '—' }}</td>
                <td>{{ $activity->description ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@if($logins->isNotEmpty())
<div class="section">
    <div class="section-title">{{ __('messages.login_log') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.email') }}</th>
                <th>{{ __('messages.ip_address') }}</th>
                <th>{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logins as $login)
            <tr>
                <td style="white-space:nowrap;">{{ $login->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $login->email ?? '—' }}</td>
                <td>{{ $login->ip_address ?? '—' }}</td>
                <td>{{ $login->successful ? __('messages.success') : __('messages.failed') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
