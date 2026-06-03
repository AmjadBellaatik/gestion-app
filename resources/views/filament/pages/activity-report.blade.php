<x-filament-panels::page>
@include('filament.partials.report-styles')

<div class="rpt-wrap">

    @include('filament.partials.period-selector')

    {{-- KPIs --}}
    <div>
        <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
        <div class="rpt-kpi-grid">
            <div class="rpt-card rpt-card-bl">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-clipboard-document-list class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_activities') }}</p>
                        <p class="rpt-kpi-val">{{ $totalActivities }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-vi">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-vi"><x-heroicon-o-users class="cl-vi"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.unique_users') }}</p>
                        <p class="rpt-kpi-val">{{ $uniqueUsers }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-arrow-right-on-rectangle class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.successful_logins') }}</p>
                        <p class="rpt-kpi-val">{{ $successfulLogins }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $failedLogins > 0 ? 'rpt-card-re' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $failedLogins > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-exclamation-circle class="{{ $failedLogins > 0 ? 'cl-re' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.failed_logins') }}</p>
                        <p class="rpt-kpi-val {{ $failedLogins > 0 ? 'cl-re' : '' }}">{{ $failedLogins }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Activity by module --}}
    @if ($byModule->isNotEmpty())
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.activity_by_module') }}</p>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            @foreach ($byModule as $module => $count)
            <span class="rpt-badge rpt-badge-bl" style="font-size:0.85rem;padding:0.3rem 0.75rem;">
                {{ $module ?: __('messages.unknown') }}: <strong>{{ $count }}</strong>
            </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Activity Log Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.activity_log') }} — {{ $periodLabel }}</p>
        @if ($activities->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
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
                    @foreach ($activities as $activity)
                    <tr>
                        <td style="white-space:nowrap;">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-weight:600;">{{ $activity->user?->name ?? '—' }}</td>
                        <td><span class="rpt-badge rpt-badge-bl">{{ $activity->module ?? '—' }}</span></td>
                        <td><span class="rpt-badge rpt-badge-cy">{{ $activity->action ?? '—' }}</span></td>
                        <td style="color:rgb(107,114,128);">{{ $activity->description ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Login Log Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.login_log') }} — {{ $periodLabel }}</p>
        @if ($logins->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.email') }}</th>
                        <th>{{ __('messages.ip_address') }}</th>
                        <th>{{ __('messages.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logins as $login)
                    <tr>
                        <td style="white-space:nowrap;">{{ $login->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $login->email ?? '—' }}</td>
                        <td style="color:rgb(107,114,128);font-family:monospace;">{{ $login->ip_address ?? '—' }}</td>
                        <td>
                            @if ($login->successful)
                                <span class="rpt-badge rpt-badge-em">{{ __('messages.success') }}</span>
                            @else
                                <span class="rpt-badge rpt-badge-re">{{ __('messages.failed') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
