<x-filament-panels::page>
@include('filament.partials.report-styles')

@php $fmt = fn(float $v) => number_format($v, 2, '.', ' '); @endphp

<style>
/* ── Report type picker ───────────────────────────────────── */
.rpt-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: .875rem;
}
.rpt-type-card {
    position: relative;
    background: #fff;
    border: 1px solid rgba(0,0,0,.08);
    border-radius: .875rem;
    padding: 1.5rem 1.125rem 1.375rem;
    cursor: pointer;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    transition: border-color .15s ease, box-shadow .15s ease, transform .12s ease;
    overflow: hidden;
}
.rpt-type-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(135deg, rgba(var(--primary-500),.06) 0%, transparent 60%);
    opacity: 0;
    transition: opacity .15s ease;
    pointer-events: none;
}
.rpt-type-card:hover::before { opacity: 1; }
.dark .rpt-type-card {
    background: rgb(30,41,59);
    border-color: rgba(255,255,255,.08);
    box-shadow: 0 1px 4px rgba(0,0,0,.25);
}
.rpt-type-card:hover {
    border-color: rgb(var(--primary-500));
    box-shadow: 0 0 0 3px rgba(var(--primary-500),.12), 0 6px 18px rgba(0,0,0,.08);
    transform: translateY(-2px);
}
.dark .rpt-type-card:hover {
    box-shadow: 0 0 0 3px rgba(var(--primary-500),.22), 0 6px 18px rgba(0,0,0,.3);
}
.rpt-type-card-icon {
    width: 3rem;
    height: 3rem;
    border-radius: .625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--primary-500),.1);
    transition: background .15s ease, transform .12s ease;
}
.rpt-type-card:hover .rpt-type-card-icon {
    background: rgba(var(--primary-500),.18);
    transform: scale(1.08);
}
.dark .rpt-type-card-icon {
    background: rgba(var(--primary-500),.15);
}
.dark .rpt-type-card:hover .rpt-type-card-icon {
    background: rgba(var(--primary-500),.25);
}
.rpt-type-card-icon svg {
    width: 1.375rem;
    height: 1.375rem;
    color: rgb(var(--primary-500));
}
.rpt-type-card-label {
    font-size: .8125rem;
    font-weight: 600;
    color: rgb(55,65,81);
    line-height: 1.35;
    letter-spacing: .01em;
}
.dark .rpt-type-card-label { color: rgb(226,232,240); }

/* ── Back button ──────────────────────────────────────────── */
.rpt-back-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .8125rem;
    font-weight: 600;
    color: var(--company-primary, #f59e0b);
    cursor: pointer;
    background: none;
    border: none;
    padding: .375rem .625rem .375rem 0;
}
.rpt-back-btn:hover { opacity: .75; text-decoration: underline; }

/* ── PDF button ───────────────────────────────────────────── */
.rpt-pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .8125rem;
    font-weight: 600;
    color: #fff;
    background: var(--company-primary, #f59e0b);
    border-radius: .5rem;
    padding: .375rem .875rem;
    text-decoration: none;
    transition: opacity .15s ease;
}
.rpt-pdf-btn:hover { opacity: .85; text-decoration: none; }
/* ── Back + PDF row ───────────────────────────────────────── */
.rpt-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

/* ── Report title ─────────────────────────────────────────── */
.rpt-report-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: rgb(17,24,39);
    margin-bottom: 1.25rem;
}
.dark .rpt-report-title { color: rgb(248,250,252); }
</style>

<div class="rpt-wrap">

@if (!$reportType)

    {{-- ── TYPE PICKER ── --}}
    <p class="rpt-section-lbl" style="margin-bottom:1.25rem;">{{ __('messages.select_report_type') }}</p>

    <div class="rpt-type-grid">

        <button class="rpt-type-card" wire:click="setReportType('sales')">
            <div class="rpt-type-card-icon"><x-heroicon-o-shopping-cart /></div>
            <span class="rpt-type-card-label">{{ __('messages.sales_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('payments')">
            <div class="rpt-type-card-icon"><x-heroicon-o-banknotes /></div>
            <span class="rpt-type-card-label">{{ __('messages.payments_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('clients')">
            <div class="rpt-type-card-icon"><x-heroicon-o-user-group /></div>
            <span class="rpt-type-card-label">{{ __('messages.clients_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('resellers')">
            <div class="rpt-type-card-icon"><x-heroicon-o-building-storefront /></div>
            <span class="rpt-type-card-label">{{ __('messages.resellers_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('stock')">
            <div class="rpt-type-card-icon"><x-heroicon-o-archive-box /></div>
            <span class="rpt-type-card-label">{{ __('messages.stock_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('motorcycles')">
            <div class="rpt-type-card-icon"><x-heroicon-o-truck /></div>
            <span class="rpt-type-card-label">{{ __('messages.motorcycles_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('repairs')">
            <div class="rpt-type-card-icon"><x-heroicon-o-wrench-screwdriver /></div>
            <span class="rpt-type-card-label">{{ __('messages.repairs_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('warranties')">
            <div class="rpt-type-card-icon"><x-heroicon-o-shield-check /></div>
            <span class="rpt-type-card-label">{{ __('messages.warranty_report') }}</span>
        </button>

        <button class="rpt-type-card" wire:click="setReportType('activity')">
            <div class="rpt-type-card-icon"><x-heroicon-o-clipboard-document-list /></div>
            <span class="rpt-type-card-label">{{ __('messages.activity_report') }}</span>
        </button>

    </div>

@else

    {{-- ── REPORT VIEW ── --}}
    <div class="rpt-top-row">
        <button class="rpt-back-btn" wire:click="setReportType(null)">
            ← {{ __('messages.select_report_type') }}
        </button>
        <a href="{{ route('reports.pdf', ['type' => $reportType, 'from' => $dateFrom, 'to' => $dateTo]) }}"
           target="_blank"
           class="rpt-pdf-btn">
            <x-heroicon-o-document-arrow-down style="width:1rem;height:1rem;"/>
            {{ __('messages.export_pdf') }}
        </a>
    </div>

    @php
        $reportLabels = [
            'sales'       => __('messages.sales_report'),
            'payments'    => __('messages.payments_report'),
            'clients'     => __('messages.clients_report'),
            'resellers'   => __('messages.resellers_report'),
            'stock'       => __('messages.stock_report'),
            'motorcycles' => __('messages.motorcycles_report'),
            'repairs'     => __('messages.repairs_report'),
            'warranties'  => __('messages.warranty_report'),
            'activity'    => __('messages.activity_report'),
        ];
    @endphp
    <p class="rpt-report-title">{{ $reportLabels[$reportType ?? ''] ?? $reportType }}</p>

    @include('filament.partials.period-selector')

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- SALES --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($reportType === 'sales')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-shopping-cart class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_sales') }}</p><p class="rpt-kpi-val">{{ $sales->count() }}</p></div></div></div>
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-banknotes class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_revenue') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalRevenue) }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_paid') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalPaid) }}</p></div></div></div>
                <div class="rpt-card {{ $totalUnpaid > 0 ? 'rpt-card-re' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $totalUnpaid > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-exclamation-circle class="{{ $totalUnpaid > 0 ? 'cl-re' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_unpaid') }}</p><p class="rpt-kpi-val {{ $totalUnpaid > 0 ? 'cl-re' : '' }}">MAD {{ $fmt($totalUnpaid) }}</p></div></div></div>
                <div class="rpt-card rpt-card-am"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-am"><x-heroicon-o-tag class="cl-am"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_discount') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalDiscount) }}</p></div></div></div>
                <div class="rpt-card rpt-card-vi"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-vi"><x-heroicon-o-calculator class="cl-vi"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.average_order') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($avgOrder) }}</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.sales_list') }} — {{ $periodLabel }}</p>
            @if($sales->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>#</th><th>{{ __('messages.date') }}</th><th>{{ __('messages.client') }} / {{ __('messages.reseller') }}</th><th>{{ __('messages.total') }}</th><th>{{ __('messages.paid_amount') }}</th><th>{{ __('messages.remaining_amount') }}</th><th>{{ __('messages.payment_status') }}</th></tr></thead>
                <tbody>
                @foreach($sales as $sale)
                @php $sc = match($sale->payment_status){ 'paid'=>'rpt-badge-em','partial'=>'rpt-badge-am',default=>'rpt-badge-re' }; @endphp
                <tr>
                    <td style="font-weight:600;color:rgb(37,99,235);">{{ $sale->sale_number }}</td>
                    <td>{{ ($sale->sale_date ?? $sale->created_at)->format('d/m/Y') }}</td>
                    <td>{{ $sale->client?->display_name ?? $sale->reseller?->name ?? '—' }}</td>
                    <td>MAD {{ $fmt($sale->total) }}</td>
                    <td>MAD {{ $fmt($sale->paid_amount) }}</td>
                    <td>{{ $sale->remaining_amount > 0 ? 'MAD '.$fmt($sale->remaining_amount) : '—' }}</td>
                    <td><span class="rpt-badge {{ $sc }}">{{ __('messages.'.$sale->payment_status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- PAYMENTS --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'payments')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-banknotes class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_collected') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalCollected) }}</p></div></div></div>
                <div class="rpt-card rpt-card-am"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-am"><x-heroicon-o-clock class="cl-am"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.pending_cheques') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalPending) }}</p></div></div></div>
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-banknotes class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.cash') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($cashTotal) }}</p></div></div></div>
                <div class="rpt-card rpt-card-vi"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-vi"><x-heroicon-o-credit-card class="cl-vi"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.card') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($cardTotal) }}</p></div></div></div>
                <div class="rpt-card rpt-card-cy"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-cy"><x-heroicon-o-document-text class="cl-cy"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.cheque') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($chequeTotal) }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-building-library class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.bank_transfer') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($transferTotal) }}</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.payments_list') }} — {{ $periodLabel }}</p>
            @if($payments->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.date') }}</th><th>{{ __('messages.sale') }}</th><th>{{ __('messages.client') }} / {{ __('messages.reseller') }}</th><th>{{ __('messages.payment_method') }}</th><th>{{ __('messages.amount') }}</th><th>{{ __('messages.status') }}</th><th>{{ __('messages.reference') }}</th></tr></thead>
                <tbody>
                @foreach($payments as $payment)
                @php
                    $mc = match($payment->payment_method){ 'cash'=>'rpt-badge-em','card'=>'rpt-badge-bl','cheque'=>'rpt-badge-am','bank_transfer'=>'rpt-badge-cy',default=>'rpt-badge-gr' };
                    $sc = match($payment->status){ 'paid'=>'rpt-badge-em','pending_validation'=>'rpt-badge-am','received'=>'rpt-badge-bl',default=>'rpt-badge-gr' };
                @endphp
                <tr>
                    <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                    <td style="font-weight:600;">{{ $payment->sale?->sale_number ?? '—' }}</td>
                    <td>{{ $payment->sale?->client?->display_name ?? $payment->sale?->reseller?->name ?? '—' }}</td>
                    <td><span class="rpt-badge {{ $mc }}">{{ __('messages.'.$payment->payment_method) }}</span></td>
                    <td style="font-weight:600;">MAD {{ $fmt($payment->amount) }}</td>
                    <td><span class="rpt-badge {{ $sc }}">{{ __('messages.'.$payment->status) }}</span></td>
                    <td style="color:rgb(107,114,128);">{{ $payment->reference ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- CLIENTS --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'clients')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-user-group class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_clients') }}</p><p class="rpt-kpi-val">{{ $totalClients }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.active') }}</p><p class="rpt-kpi-val">{{ $activeClients }}</p></div></div></div>
                <div class="rpt-card {{ $blockedClients > 0 ? 'rpt-card-re' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $blockedClients > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-no-symbol class="{{ $blockedClients > 0 ? 'cl-re' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.blocked') }}</p><p class="rpt-kpi-val {{ $blockedClients > 0 ? 'cl-re' : '' }}">{{ $blockedClients }}</p></div></div></div>
                <div class="rpt-card rpt-card-vi"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-vi"><x-heroicon-o-user class="cl-vi"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.person') }}</p><p class="rpt-kpi-val">{{ $persons }}</p></div></div></div>
                <div class="rpt-card rpt-card-cy"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-cy"><x-heroicon-o-building-office class="cl-cy"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.company') }}</p><p class="rpt-kpi-val">{{ $companies }}</p></div></div></div>
                <div class="rpt-card rpt-card-am"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-am"><x-heroicon-o-building-office-2 class="cl-am"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.new_in_period') }}</p><p class="rpt-kpi-val">{{ $newInPeriod }}</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.clients_list') }}</p>
            @if($clients->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.name') }}</th><th>{{ __('messages.type') }}</th><th>{{ __('messages.phone') }}</th><th>{{ __('messages.email') }}</th><th>{{ __('messages.total_sales') }}</th><th>{{ __('messages.total_revenue') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($clients as $client)
                @php
                    $tc = match($client->client_type){ 'person'=>'rpt-badge-bl','company'=>'rpt-badge-cy','administration'=>'rpt-badge-am',default=>'rpt-badge-gr' };
                    $sc = $client->is_blocked ? 'rpt-badge-re' : ($client->is_active ? 'rpt-badge-em' : 'rpt-badge-gr');
                    $sl = $client->is_blocked ? __('messages.blocked') : ($client->is_active ? __('messages.active') : __('messages.inactive'));
                @endphp
                <tr>
                    <td style="font-weight:600;">{{ $client->display_name }}</td>
                    <td><span class="rpt-badge {{ $tc }}">{{ __('messages.'.$client->client_type) }}</span></td>
                    <td>{{ $client->phone ?? '—' }}</td>
                    <td>{{ $client->email ?? '—' }}</td>
                    <td>{{ $client->sales_count }}</td>
                    <td>MAD {{ $fmt($client->sales_sum_total ?? 0) }}</td>
                    <td><span class="rpt-badge {{ $sc }}">{{ $sl }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- RESELLERS --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'resellers')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-building-storefront class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_resellers') }}</p><p class="rpt-kpi-val">{{ $totalResellers }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-shopping-cart class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_orders') }}</p><p class="rpt-kpi-val">{{ $totalOrders }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_paid') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalPaid) }}</p></div></div></div>
                <div class="rpt-card {{ $totalDebt > 0 ? 'rpt-card-re' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $totalDebt > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-exclamation-triangle class="{{ $totalDebt > 0 ? 'cl-re' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_debt') }}</p><p class="rpt-kpi-val {{ $totalDebt > 0 ? 'cl-re' : '' }}">MAD {{ $fmt($totalDebt) }}</p></div></div></div>
                <div class="rpt-card {{ $withDebt > 0 ? 'rpt-card-am' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $withDebt > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-users class="{{ $withDebt > 0 ? 'cl-am' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.with_outstanding_debt') }}</p><p class="rpt-kpi-val {{ $withDebt > 0 ? 'cl-am' : '' }}">{{ $withDebt }}</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.resellers_list') }}</p>
            @if($resellers->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.name') }}</th><th>{{ __('messages.phone') }}</th><th>{{ __('messages.email') }}</th><th>{{ __('messages.orders') }}</th><th>{{ __('messages.total_paid') }}</th><th>{{ __('messages.current_debt') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($resellers as $reseller)
                <tr>
                    <td style="font-weight:600;">{{ $reseller->name }}</td>
                    <td>{{ $reseller->phone ?? '—' }}</td>
                    <td>{{ $reseller->email ?? '—' }}</td>
                    <td>{{ $reseller->total_orders }}</td>
                    <td>MAD {{ $fmt($reseller->total_paid ?? 0) }}</td>
                    <td>@if($reseller->current_debt > 0)<span class="rpt-badge rpt-badge-re">MAD {{ $fmt($reseller->current_debt) }}</span>@else<span class="rpt-badge rpt-badge-em">{{ __('messages.settled') }}</span>@endif</td>
                    <td><span class="rpt-badge {{ $reseller->is_blocked ? 'rpt-badge-re' : ($reseller->is_active ? 'rpt-badge-em' : 'rpt-badge-gr') }}">{{ $reseller->is_blocked ? __('messages.blocked') : ($reseller->is_active ? __('messages.active') : __('messages.inactive')) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- STOCK --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'stock')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-archive-box class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_products') }}</p><p class="rpt-kpi-val">{{ $totalProducts }}</p></div></div></div>
                <div class="rpt-card {{ $lowStockCount > 0 ? 'rpt-card-re' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $lowStockCount > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-archive-box-x-mark class="{{ $lowStockCount > 0 ? 'cl-re' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.low_stock') }}</p><p class="rpt-kpi-val {{ $lowStockCount > 0 ? 'cl-re' : '' }}">{{ $lowStockCount }}</p></div></div></div>
                <div class="rpt-card rpt-card-cy"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-cy"><x-heroicon-o-currency-dollar class="cl-cy"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.stock_valuation') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalValue) }}</p></div></div></div>
                <div class="rpt-card rpt-card-vi"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-vi"><x-heroicon-o-arrow-path class="cl-vi"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.movements_in_period') }}</p><p class="rpt-kpi-val">{{ $movementsCount }}</p><p class="rpt-kpi-sub">↑ {{ $entriesCount }} {{ __('messages.in') }} &nbsp; ↓ {{ $exitsCount }} {{ __('messages.out') }}</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.products_inventory') }}</p>
            @if($products->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.product') }}</th><th>{{ __('messages.sku') }}</th><th>{{ __('messages.current_stock') }}</th><th>{{ __('messages.alert_threshold') }}</th><th>{{ __('messages.purchase_price') }}</th><th>{{ __('messages.selling_price') }}</th><th>{{ __('messages.stock_value') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($products as $product)
                <tr>
                    <td style="font-weight:600;">{{ $product->name }}</td>
                    <td style="color:rgb(107,114,128);">{{ $product->sku ?? '—' }}</td>
                    <td style="font-weight:600;">{{ number_format($product->current_qty, 2) }}</td>
                    <td>{{ $product->stock_alert > 0 ? $product->stock_alert : '—' }}</td>
                    <td>{{ $product->purchase_price ? 'MAD '.$fmt($product->purchase_price) : '—' }}</td>
                    <td>MAD {{ $fmt($product->selling_price) }}</td>
                    <td style="font-weight:600;">MAD {{ $fmt($product->stock_value) }}</td>
                    <td>@if($product->is_low)<span class="rpt-badge rpt-badge-re">{{ __('messages.low_stock') }}</span>@elseif($product->current_qty <= 0)<span class="rpt-badge rpt-badge-am">{{ __('messages.out_of_stock') }}</span>@else<span class="rpt-badge rpt-badge-em">{{ __('messages.in_stock') }}</span>@endif</td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

        @if($movements->isNotEmpty())
        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.stock_movements') }} — {{ $periodLabel }}</p>
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.date') }}</th><th>{{ __('messages.product') }}</th><th>{{ __('messages.type') }}</th><th>{{ __('messages.quantity') }}</th><th>{{ __('messages.unit_cost') }}</th><th>{{ __('messages.reference') }}</th></tr></thead>
                <tbody>
                @foreach($movements as $mv)
                <tr>
                    <td>{{ $mv->created_at->format('d/m/Y') }}</td>
                    <td>{{ $mv->product?->name ?? '—' }}</td>
                    <td><span class="rpt-badge {{ in_array($mv->type, ['entry','in']) ? 'rpt-badge-em' : 'rpt-badge-re' }}">{{ __('messages.'.$mv->type) }}</span></td>
                    <td style="font-weight:600;">{{ number_format($mv->quantity, 2) }}</td>
                    <td>{{ $mv->unit_cost ? 'MAD '.$fmt($mv->unit_cost) : '—' }}</td>
                    <td style="color:rgb(107,114,128);">{{ $mv->reference ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
        @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- MOTORCYCLES --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'motorcycles')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-truck class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_units') }}</p><p class="rpt-kpi-val">{{ $totalUnits }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-archive-box class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.in_stock') }}</p><p class="rpt-kpi-val">{{ $inStock }}</p></div></div></div>
                <div class="rpt-card {{ $onHold > 0 ? 'rpt-card-am' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $onHold > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-pause-circle class="{{ $onHold > 0 ? 'cl-am' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.on_hold') }}</p><p class="rpt-kpi-val {{ $onHold > 0 ? 'cl-am' : '' }}">{{ $onHold }}</p></div></div></div>
                <div class="rpt-card rpt-card-vi"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-vi"><x-heroicon-o-check-badge class="cl-vi"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.sold') }}</p><p class="rpt-kpi-val">{{ $sold }}</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.motorcycles_list') }}</p>
            @if($units->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.model') }}</th><th>{{ __('messages.chassis_number') }}</th><th>{{ __('messages.engine_number') }}</th><th>{{ __('messages.color') }}</th><th>{{ __('messages.year') }}</th><th>{{ __('messages.client') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($units as $unit)
                @php $sc = match($unit->status){ 'in_stock','available'=>'rpt-badge-em','on_hold'=>'rpt-badge-am','sold'=>'rpt-badge-vi','in_repair'=>'rpt-badge-cy',default=>'rpt-badge-gr' }; @endphp
                <tr>
                    <td style="font-weight:600;">{{ $unit->motorcycleModel?->brand }} {{ $unit->motorcycleModel?->model }}</td>
                    <td style="font-family:monospace;color:rgb(107,114,128);">{{ $unit->chassis_number ?? '—' }}</td>
                    <td style="font-family:monospace;color:rgb(107,114,128);">{{ $unit->engine_number ?? '—' }}</td>
                    <td>{{ $unit->color ?? '—' }}</td>
                    <td>{{ $unit->year ?? '—' }}</td>
                    <td>{{ $unit->client?->display_name ?? '—' }}</td>
                    <td><span class="rpt-badge {{ $sc }}">{{ __('messages.'.$unit->status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- REPAIRS --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'repairs')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-wrench-screwdriver class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_repairs') }}</p><p class="rpt-kpi-val">{{ $totalRepairs }}</p></div></div></div>
                <div class="rpt-card {{ $openCount > 0 ? 'rpt-card-am' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $openCount > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-clock class="{{ $openCount > 0 ? 'cl-am' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.open') }}</p><p class="rpt-kpi-val {{ $openCount > 0 ? 'cl-am' : '' }}">{{ $openCount }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.completed') }}</p><p class="rpt-kpi-val">{{ $completedCount }}</p></div></div></div>
                <div class="rpt-card rpt-card-cy"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-cy"><x-heroicon-o-shield-check class="cl-cy"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.under_warranty') }}</p><p class="rpt-kpi-val">{{ $warrantyCount }}</p></div></div></div>
                <div class="rpt-card rpt-card-vi"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-vi"><x-heroicon-o-banknotes class="cl-vi"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_revenue') }}</p><p class="rpt-kpi-val">MAD {{ $fmt($totalRevenue) }}</p></div></div></div>
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-calculator class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.avg_repair_time') }}</p><p class="rpt-kpi-val">{{ $avgTime }}h</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.repairs_list') }} — {{ $periodLabel }}</p>
            @if($repairs->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.ticket') }}</th><th>{{ __('messages.date') }}</th><th>{{ __('messages.client') }}</th><th>{{ __('messages.motorcycle') }}</th><th>{{ __('messages.technician') }}</th><th>{{ __('messages.priority') }}</th><th>{{ __('messages.total_cost') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($repairs as $repair)
                @php
                    $sc = match($repair->status){ 'completed','delivered'=>'rpt-badge-em','in_progress'=>'rpt-badge-bl','open','assigned'=>'rpt-badge-am','diagnostic'=>'rpt-badge-cy','cancelled'=>'rpt-badge-re',default=>'rpt-badge-gr' };
                    $pc = match($repair->priority ?? 'normal'){ 'urgent'=>'rpt-badge-re','high'=>'rpt-badge-am','normal'=>'rpt-badge-bl',default=>'rpt-badge-gr' };
                @endphp
                <tr>
                    <td style="font-weight:600;color:rgb(37,99,235);">{{ $repair->ticket_number ?? '#'.$repair->id }}</td>
                    <td>{{ $repair->created_at->format('d/m/Y') }}</td>
                    <td>{{ $repair->client?->display_name ?? '—' }}</td>
                    <td>{{ $repair->motorcycleUnit?->motorcycleModel?->brand }} {{ $repair->motorcycleUnit?->motorcycleModel?->model ?? '—' }}</td>
                    <td>{{ $repair->technician?->name ?? '—' }}</td>
                    <td><span class="rpt-badge {{ $pc }}">{{ __('messages.'.($repair->priority ?? 'normal')) }}</span></td>
                    <td>{{ $repair->total_cost > 0 ? 'MAD '.$fmt($repair->total_cost) : '—' }}</td>
                    <td><span class="rpt-badge {{ $sc }}">{{ __('messages.'.$repair->status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- WARRANTIES --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'warranties')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-shield-check class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_warranties') }}</p><p class="rpt-kpi-val">{{ $warranties->count() }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-check-circle class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.active') }}</p><p class="rpt-kpi-val">{{ $activeCount }}</p></div></div></div>
                <div class="rpt-card rpt-card-re"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-re"><x-heroicon-o-x-circle class="cl-re"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.expired') }}</p><p class="rpt-kpi-val cl-re">{{ $expiredCount }}</p></div></div></div>
                <div class="rpt-card {{ $expiringSoon > 0 ? 'rpt-card-am' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $expiringSoon > 0 ? 'bg-am' : 'bg-em' }}"><x-heroicon-o-exclamation-triangle class="{{ $expiringSoon > 0 ? 'cl-am' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.expiring_soon') }}</p><p class="rpt-kpi-val {{ $expiringSoon > 0 ? 'cl-am' : '' }}">{{ $expiringSoon }}</p></div></div></div>
            </div>
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.warranties_list') }} — {{ $periodLabel }}</p>
            @if($warranties->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.item') }}</th><th>{{ __('messages.client') }}</th><th>{{ __('messages.start_date') }}</th><th>{{ __('messages.end_date') }}</th><th>{{ __('messages.km_limit') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($warranties as $warranty)
                @php
                    $sc = match($warranty->status){ 'active'=>'rpt-badge-em','expired'=>'rpt-badge-re','voided'=>'rpt-badge-gr',default=>'rpt-badge-gr' };
                    $il = $warranty->motorcycleUnit ? ($warranty->motorcycleUnit->motorcycleModel?->brand.' '.$warranty->motorcycleUnit->motorcycleModel?->model.' — '.$warranty->motorcycleUnit->chassis_number) : ($warranty->product?->name ?? '—');
                @endphp
                <tr>
                    <td style="font-weight:600;">{{ $il }}</td>
                    <td>{{ $warranty->client?->display_name ?? '—' }}</td>
                    <td>{{ $warranty->start_date ? $warranty->start_date->format('d/m/Y') : '—' }}</td>
                    <td>{{ $warranty->end_date ? $warranty->end_date->format('d/m/Y') : '—' }}</td>
                    <td>{{ $warranty->km_limit ? number_format($warranty->km_limit).' km' : '—' }}</td>
                    <td><span class="rpt-badge {{ $sc }}">{{ __('messages.'.$warranty->status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ACTIVITY --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @elseif($reportType === 'activity')

        <div>
            <p class="rpt-section-lbl">{{ __('messages.summary') }}</p>
            <div class="rpt-kpi-grid">
                <div class="rpt-card rpt-card-bl"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-bl"><x-heroicon-o-clipboard-document-list class="cl-bl"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.total_activities') }}</p><p class="rpt-kpi-val">{{ $totalActivities }}</p></div></div></div>
                <div class="rpt-card rpt-card-vi"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-vi"><x-heroicon-o-users class="cl-vi"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.unique_users') }}</p><p class="rpt-kpi-val">{{ $uniqueUsers }}</p></div></div></div>
                <div class="rpt-card rpt-card-em"><div class="rpt-kpi"><div class="rpt-kpi-icon bg-em"><x-heroicon-o-arrow-right-on-rectangle class="cl-em"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.successful_logins') }}</p><p class="rpt-kpi-val">{{ $successfulLogins }}</p></div></div></div>
                <div class="rpt-card {{ $failedLogins > 0 ? 'rpt-card-re' : 'rpt-card-em' }}"><div class="rpt-kpi"><div class="rpt-kpi-icon {{ $failedLogins > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-exclamation-circle class="{{ $failedLogins > 0 ? 'cl-re' : 'cl-em' }}"/></div><div class="rpt-kpi-body"><p class="rpt-kpi-lbl">{{ __('messages.failed_logins') }}</p><p class="rpt-kpi-val {{ $failedLogins > 0 ? 'cl-re' : '' }}">{{ $failedLogins }}</p></div></div></div>
            </div>
        </div>

        @if($byModule->isNotEmpty())
        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:.75rem;">{{ __('messages.activity_by_module') }}</p>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                @foreach($byModule as $module => $count)
                <span class="rpt-badge rpt-badge-bl" style="font-size:.85rem;padding:.3rem .75rem;">{{ $module ?: __('messages.unknown') }}: <strong>{{ $count }}</strong></span>
                @endforeach
            </div>
        </div>
        @endif

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.activity_log') }} — {{ $periodLabel }}</p>
            @if($activities->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.date') }}</th><th>{{ __('messages.user') }}</th><th>{{ __('messages.module') }}</th><th>{{ __('messages.action') }}</th><th>{{ __('messages.description') }}</th></tr></thead>
                <tbody>
                @foreach($activities as $activity)
                <tr>
                    <td style="white-space:nowrap;">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                    <td style="font-weight:600;">{{ $activity->user?->name ?? '—' }}</td>
                    <td><span class="rpt-badge rpt-badge-bl">{{ $activity->module ?? '—' }}</span></td>
                    <td><span class="rpt-badge rpt-badge-cy">{{ $activity->action ?? '—' }}</span></td>
                    <td style="color:rgb(107,114,128);">{{ $activity->description ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

        <div class="rpt-card">
            <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.login_log') }} — {{ $periodLabel }}</p>
            @if($logins->isEmpty()) <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p> @else
            <div class="rpt-table-wrap"><table class="rpt-table">
                <thead><tr><th>{{ __('messages.date') }}</th><th>{{ __('messages.email') }}</th><th>{{ __('messages.ip_address') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($logins as $login)
                <tr>
                    <td style="white-space:nowrap;">{{ $login->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $login->email ?? '—' }}</td>
                    <td style="font-family:monospace;color:rgb(107,114,128);">{{ $login->ip_address ?? '—' }}</td>
                    <td>@if($login->successful)<span class="rpt-badge rpt-badge-em">{{ __('messages.success') }}</span>@else<span class="rpt-badge rpt-badge-re">{{ __('messages.failed') }}</span>@endif</td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>

    @endif {{-- end reportType switch --}}

@endif {{-- end type picker / report content --}}

</div>
</x-filament-panels::page>
