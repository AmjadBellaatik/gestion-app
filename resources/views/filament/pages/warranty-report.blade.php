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
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-shield-check class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_warranties') }}</p>
                        <p class="rpt-kpi-val">{{ $warranties->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.active') }}</p>
                        <p class="rpt-kpi-val">{{ $activeCount }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-re">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-re"><x-heroicon-o-x-circle class="cl-re"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.expired') }}</p>
                        <p class="rpt-kpi-val cl-re">{{ $expiredCount }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $expiringSoon > 0 ? 'rpt-card-am' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $expiringSoon > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-exclamation-triangle class="{{ $expiringSoon > 0 ? 'cl-am' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.expiring_soon') }}</p>
                        <p class="rpt-kpi-val {{ $expiringSoon > 0 ? 'cl-am' : '' }}">{{ $expiringSoon }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Warranties Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.warranties_list') }} — {{ $periodLabel }}</p>
        @if ($warranties->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
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
                    @foreach ($warranties as $warranty)
                    @php
                        $statusClass = match($warranty->status) {
                            'active'  => 'rpt-badge-em',
                            'expired' => 'rpt-badge-re',
                            'voided'  => 'rpt-badge-gr',
                            default   => 'rpt-badge-gr',
                        };
                        $itemLabel = $warranty->motorcycleUnit
                            ? ($warranty->motorcycleUnit->motorcycleModel?->brand.' '.$warranty->motorcycleUnit->motorcycleModel?->model.' — '.$warranty->motorcycleUnit->chassis_number)
                            : ($warranty->product?->name ?? '—');
                    @endphp
                    <tr>
                        <td style="font-weight:600;">{{ $itemLabel }}</td>
                        <td>{{ $warranty->client?->display_name ?? '—' }}</td>
                        <td>{{ $warranty->start_date ? $warranty->start_date->format('d/m/Y') : '—' }}</td>
                        <td>{{ $warranty->end_date ? $warranty->end_date->format('d/m/Y') : '—' }}</td>
                        <td>{{ $warranty->km_limit ? number_format($warranty->km_limit).' km' : '—' }}</td>
                        <td><span class="rpt-badge {{ $statusClass }}">{{ __('messages.'.$warranty->status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
