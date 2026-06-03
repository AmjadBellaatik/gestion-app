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
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-truck class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_units') }}</p>
                        <p class="rpt-kpi-val">{{ $totalUnits }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-archive-box class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.in_stock') }}</p>
                        <p class="rpt-kpi-val">{{ $inStock }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $onHold > 0 ? 'rpt-card-am' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $onHold > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-pause-circle class="{{ $onHold > 0 ? 'cl-am' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.on_hold') }}</p>
                        <p class="rpt-kpi-val {{ $onHold > 0 ? 'cl-am' : '' }}">{{ $onHold }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-vi">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-vi"><x-heroicon-o-check-badge class="cl-vi"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.sold') }}</p>
                        <p class="rpt-kpi-val">{{ $sold }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Units Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.motorcycles_list') }}</p>
        @if ($units->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
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
                    @foreach ($units as $unit)
                    @php
                        $statusClass = match($unit->status) {
                            'in_stock', 'available' => 'rpt-badge-em',
                            'on_hold'               => 'rpt-badge-am',
                            'sold'                  => 'rpt-badge-vi',
                            'in_repair'             => 'rpt-badge-cy',
                            default                 => 'rpt-badge-gr',
                        };
                    @endphp
                    <tr>
                        <td style="font-weight:600;">{{ $unit->motorcycleModel?->brand }} {{ $unit->motorcycleModel?->model }}</td>
                        <td style="color:rgb(107,114,128);font-family:monospace;">{{ $unit->chassis_number ?? '—' }}</td>
                        <td style="color:rgb(107,114,128);font-family:monospace;">{{ $unit->engine_number ?? '—' }}</td>
                        <td>{{ $unit->color ?? '—' }}</td>
                        <td>{{ $unit->year ?? '—' }}</td>
                        <td>{{ $unit->client?->display_name ?? '—' }}</td>
                        <td><span class="rpt-badge {{ $statusClass }}">{{ __('messages.'.$unit->status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
