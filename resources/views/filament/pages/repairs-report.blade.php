<x-filament-panels::page>
@include('filament.partials.report-styles')

@php $fmt = fn(float $v) => number_format($v, 2, '.', ' '); @endphp

<div class="rpt-wrap">

    @include('filament.partials.period-selector')

    {{-- KPIs --}}
    <div>
        <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
        <div class="rpt-kpi-grid">
            <div class="rpt-card rpt-card-bl">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-wrench-screwdriver class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_repairs') }}</p>
                        <p class="rpt-kpi-val">{{ $totalRepairs }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $openCount > 0 ? 'rpt-card-am' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $openCount > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-clock class="{{ $openCount > 0 ? 'cl-am' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.open') }}</p>
                        <p class="rpt-kpi-val {{ $openCount > 0 ? 'cl-am' : '' }}">{{ $openCount }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.completed') }}</p>
                        <p class="rpt-kpi-val">{{ $completedCount }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-cy">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-cy"><x-heroicon-o-shield-check class="cl-cy"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.under_warranty') }}</p>
                        <p class="rpt-kpi-val">{{ $warrantyCount }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-vi">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-vi"><x-heroicon-o-banknotes class="cl-vi"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_revenue') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($totalRevenue) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-bl">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-calculator class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.avg_repair_time') }}</p>
                        <p class="rpt-kpi-val">{{ $avgTime }}h</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Repairs Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.repairs_list') }} — {{ $periodLabel }}</p>
        @if ($repairs->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.ticket') }}</th>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.client') }}</th>
                        <th>{{ __('messages.motorcycle') }}</th>
                        <th>{{ __('messages.technician') }}</th>
                        <th>{{ __('messages.priority') }}</th>
                        <th>{{ __('messages.total_cost') }}</th>
                        <th>{{ __('messages.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($repairs as $repair)
                    @php
                        $statusClass = match($repair->status) {
                            'completed', 'delivered' => 'rpt-badge-em',
                            'in_progress'            => 'rpt-badge-bl',
                            'open', 'assigned'       => 'rpt-badge-am',
                            'diagnostic'             => 'rpt-badge-cy',
                            'cancelled'              => 'rpt-badge-re',
                            default                  => 'rpt-badge-gr',
                        };
                        $priorityClass = match($repair->priority ?? 'normal') {
                            'urgent' => 'rpt-badge-re',
                            'high'   => 'rpt-badge-am',
                            'normal' => 'rpt-badge-bl',
                            default  => 'rpt-badge-gr',
                        };
                    @endphp
                    <tr>
                        <td style="font-weight:600;color:rgb(37,99,235);">{{ $repair->ticket_number ?? '#'.$repair->id }}</td>
                        <td>{{ $repair->created_at->format('d/m/Y') }}</td>
                        <td>{{ $repair->client?->display_name ?? '—' }}</td>
                        <td>{{ $repair->motorcycleUnit?->motorcycleModel?->brand }} {{ $repair->motorcycleUnit?->motorcycleModel?->model ?? '—' }}</td>
                        <td>{{ $repair->technician?->name ?? '—' }}</td>
                        <td><span class="rpt-badge {{ $priorityClass }}">{{ __('messages.'.($repair->priority ?? 'normal')) }}</span></td>
                        <td style="font-weight:600;">{{ $repair->total_cost > 0 ? 'MAD '.$fmt($repair->total_cost) : '—' }}</td>
                        <td><span class="rpt-badge {{ $statusClass }}">{{ __('messages.'.$repair->status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
