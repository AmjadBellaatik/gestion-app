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
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-user-group class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_clients') }}</p>
                        <p class="rpt-kpi-val">{{ $totalClients }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.active') }}</p>
                        <p class="rpt-kpi-val">{{ $activeClients }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $blockedClients > 0 ? 'rpt-card-re' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $blockedClients > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-no-symbol class="{{ $blockedClients > 0 ? 'cl-re' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.blocked') }}</p>
                        <p class="rpt-kpi-val {{ $blockedClients > 0 ? 'cl-re' : '' }}">{{ $blockedClients }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-vi">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-vi"><x-heroicon-o-user class="cl-vi"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.person') }}</p>
                        <p class="rpt-kpi-val">{{ $persons }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-cy">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-cy"><x-heroicon-o-building-office class="cl-cy"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.company') }}</p>
                        <p class="rpt-kpi-val">{{ $companies }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-am">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-am"><x-heroicon-o-building-office-2 class="cl-am"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.new_in_period') }}</p>
                        <p class="rpt-kpi-val">{{ $newInPeriod }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.clients_list') }}</p>
        @if ($clients->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.type') }}</th>
                        <th>{{ __('messages.phone') }}</th>
                        <th>{{ __('messages.email') }}</th>
                        <th>{{ __('messages.total_sales') }}</th>
                        <th>{{ __('messages.total_revenue') }}</th>
                        <th>{{ __('messages.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                    @php
                        $typeClass = match($client->client_type) {
                            'person'         => 'rpt-badge-bl',
                            'company'        => 'rpt-badge-cy',
                            'administration' => 'rpt-badge-am',
                            default          => 'rpt-badge-gr',
                        };
                        $statusClass = $client->is_blocked ? 'rpt-badge-re' : ($client->is_active ? 'rpt-badge-em' : 'rpt-badge-gr');
                        $statusLabel = $client->is_blocked ? __('messages.blocked') : ($client->is_active ? __('messages.active') : __('messages.inactive'));
                    @endphp
                    <tr>
                        <td style="font-weight:600;">{{ $client->display_name }}</td>
                        <td><span class="rpt-badge {{ $typeClass }}">{{ __('messages.'.$client->client_type) }}</span></td>
                        <td>{{ $client->phone ?? '—' }}</td>
                        <td>{{ $client->email ?? '—' }}</td>
                        <td>{{ $client->sales_count }}</td>
                        <td>MAD {{ $fmt($client->sales_sum_total ?? 0) }}</td>
                        <td><span class="rpt-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
