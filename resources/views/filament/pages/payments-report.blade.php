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
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-banknotes class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_collected') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($totalCollected) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-am">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-am"><x-heroicon-o-clock class="cl-am"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.pending_cheques') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($totalPending) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-bl">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-banknotes class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.cash') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($cashTotal) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-vi">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-vi"><x-heroicon-o-credit-card class="cl-vi"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.card') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($cardTotal) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-cy">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-cy"><x-heroicon-o-document-text class="cl-cy"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.cheque') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($chequeTotal) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-em">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-em"><x-heroicon-o-building-library class="cl-em"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.bank_transfer') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($transferTotal) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.payments_list') }}</p>
        @if ($payments->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.sale') }}</th>
                        <th>{{ __('messages.client') }} / {{ __('messages.reseller') }}</th>
                        <th>{{ __('messages.payment_method') }}</th>
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.reference') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                    @php
                        $methodClass = match($payment->payment_method) {
                            'cash'          => 'rpt-badge-em',
                            'card'          => 'rpt-badge-bl',
                            'cheque'        => 'rpt-badge-am',
                            'bank_transfer' => 'rpt-badge-cy',
                            default         => 'rpt-badge-gr',
                        };
                        $statusClass = match($payment->status) {
                            'paid'                 => 'rpt-badge-em',
                            'pending_validation'   => 'rpt-badge-am',
                            'received'             => 'rpt-badge-bl',
                            default                => 'rpt-badge-gr',
                        };
                    @endphp
                    <tr>
                        <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                        <td style="font-weight:600;">{{ $payment->sale?->sale_number ?? '—' }}</td>
                        <td>{{ $payment->sale?->client?->display_name ?? $payment->sale?->reseller?->name ?? '—' }}</td>
                        <td><span class="rpt-badge {{ $methodClass }}">{{ __('messages.'.$payment->payment_method) }}</span></td>
                        <td style="font-weight:600;">MAD {{ $fmt($payment->amount) }}</td>
                        <td><span class="rpt-badge {{ $statusClass }}">{{ __('messages.'.$payment->status) }}</span></td>
                        <td style="color:rgb(107,114,128);">{{ $payment->reference ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
