<x-filament-panels::page>
@include('filament.partials.report-styles')

@php $fmt = fn(float $v) => number_format($v, 2, '.', ' '); @endphp

<div class="rpt-wrap">

    @include('filament.partials.period-selector')

    {{-- KPIs --}}
    <div>
        <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
        <div class="rpt-kpi-grid">

            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-shopping-cart class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_sales') }}</p>
                        <p class="rpt-kpi-val">{{ $sales->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="rpt-card rpt-card-bl">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-banknotes class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_revenue') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($totalRevenue) }}</p>
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

            <div class="rpt-card {{ $totalUnpaid > 0 ? 'rpt-card-re' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $totalUnpaid > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-exclamation-circle class="{{ $totalUnpaid > 0 ? 'cl-re' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_unpaid') }}</p>
                        <p class="rpt-kpi-val {{ $totalUnpaid > 0 ? 'cl-re' : '' }}">MAD {{ $fmt($totalUnpaid) }}</p>
                    </div>
                </div>
            </div>

            <div class="rpt-card rpt-card-am">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-am"><x-heroicon-o-tag class="cl-am"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_discount') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($totalDiscount) }}</p>
                    </div>
                </div>
            </div>

            <div class="rpt-card rpt-card-vi">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-vi"><x-heroicon-o-calculator class="cl-vi"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.average_order') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($avgOrder) }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.sales_list') }}</p>
        @if ($sales->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.client') }} / {{ __('messages.reseller') }}</th>
                        <th>{{ __('messages.total') }}</th>
                        <th>{{ __('messages.discount_amount') }}</th>
                        <th>{{ __('messages.paid_amount') }}</th>
                        <th>{{ __('messages.remaining_amount') }}</th>
                        <th>{{ __('messages.payment_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                    @php
                        $statusClass = match($sale->payment_status) {
                            'paid'    => 'rpt-badge-em',
                            'partial' => 'rpt-badge-am',
                            default   => 'rpt-badge-re',
                        };
                    @endphp
                    <tr>
                        <td style="font-weight:600;color:rgb(37,99,235);">{{ $sale->sale_number }}</td>
                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                        <td>{{ $sale->client?->display_name ?? $sale->reseller?->name ?? '—' }}</td>
                        <td>MAD {{ $fmt($sale->total) }}</td>
                        <td>{{ $sale->discount > 0 ? 'MAD '.$fmt($sale->discount) : '—' }}</td>
                        <td>MAD {{ $fmt($sale->paid_amount) }}</td>
                        <td>{{ $sale->remaining_amount > 0 ? 'MAD '.$fmt($sale->remaining_amount) : '—' }}</td>
                        <td><span class="rpt-badge {{ $statusClass }}">{{ __('messages.'.$sale->payment_status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
