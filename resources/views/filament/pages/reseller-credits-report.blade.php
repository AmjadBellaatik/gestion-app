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
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-building-storefront class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_resellers') }}</p>
                        <p class="rpt-kpi-val">{{ $totalResellers }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-shopping-cart class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_orders') }}</p>
                        <p class="rpt-kpi-val">{{ $totalOrders }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_paid') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($totalPaid) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $totalDebt > 0 ? 'rpt-card-re' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $totalDebt > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-exclamation-triangle class="{{ $totalDebt > 0 ? 'cl-re' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_debt') }}</p>
                        <p class="rpt-kpi-val {{ $totalDebt > 0 ? 'cl-re' : '' }}">MAD {{ $fmt($totalDebt) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $withDebt > 0 ? 'rpt-card-am' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $withDebt > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-users class="{{ $withDebt > 0 ? 'cl-am' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.with_outstanding_debt') }}</p>
                        <p class="rpt-kpi-val {{ $withDebt > 0 ? 'cl-am' : '' }}">{{ $withDebt }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.resellers_list') }}</p>
        @if ($resellers->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.phone') }}</th>
                        <th>{{ __('messages.email') }}</th>
                        <th>{{ __('messages.orders') }}</th>
                        <th>{{ __('messages.total_paid') }}</th>
                        <th>{{ __('messages.current_debt') }}</th>
                        <th>{{ __('messages.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resellers as $reseller)
                    <tr>
                        <td style="font-weight:600;">{{ $reseller->name }}</td>
                        <td>{{ $reseller->phone ?? '—' }}</td>
                        <td>{{ $reseller->email ?? '—' }}</td>
                        <td>{{ $reseller->total_orders }}</td>
                        <td>MAD {{ $fmt($reseller->total_paid) }}</td>
                        <td>
                            @if ($reseller->current_debt > 0)
                                <span class="rpt-badge rpt-badge-re">MAD {{ $fmt($reseller->current_debt) }}</span>
                            @else
                                <span class="rpt-badge rpt-badge-em">{{ __('messages.settled') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="rpt-badge {{ $reseller->is_blocked ? 'rpt-badge-re' : ($reseller->is_active ? 'rpt-badge-em' : 'rpt-badge-gr') }}">
                                {{ $reseller->is_blocked ? __('messages.blocked') : ($reseller->is_active ? __('messages.active') : __('messages.inactive')) }}
                            </span>
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
