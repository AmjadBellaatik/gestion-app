<x-filament-panels::page>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endassets

<style>
/* ── Reset / Base ─────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Layout ──────────────────────────────────────────────── */
.db-wrap        { display:flex; flex-direction:column; gap:1.75rem; }
.db-section     { display:flex; flex-direction:column; gap:.875rem; }
.db-section-lbl { font-size:.6875rem; font-weight:700; letter-spacing:.1em;
                   text-transform:uppercase; color:rgb(156,163,175); }
.dark .db-section-lbl { color:rgb(107,114,128); }

/* KPI grid: 1 → 2 → 4 columns */
.db-kpi-grid { display:grid; gap:1rem;
               grid-template-columns: repeat(1, 1fr); }
@media (min-width:640px)  { .db-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width:1024px) { .db-kpi-grid { grid-template-columns: repeat(4, 1fr); } }

/* 2-col grid for charts */
.db-chart-grid { display:grid; gap:1.25rem;
                 grid-template-columns: 1fr; }
@media (min-width:1024px) { .db-chart-grid { grid-template-columns: 3fr 2fr; } }

/* ── Card ─────────────────────────────────────────────────── */
.db-card {
    background: #fff;
    border-radius: .875rem;
    padding: 1.25rem 1.375rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    border: 1px solid rgba(0,0,0,.06);
    min-width: 0;
}
.dark .db-card {
    background: rgb(30,41,59);
    border-color: rgba(255,255,255,.07);
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
}

/* ── KPI Card ─────────────────────────────────────────────── */
.db-kpi {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.db-kpi-icon {
    flex-shrink: 0;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: .625rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.db-kpi-icon svg { width: 1.25rem; height: 1.25rem; }

.db-kpi-body  { flex: 1; min-width: 0; }
.db-kpi-lbl   { font-size: .8125rem; color: rgb(107,114,128);
                 white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dark .db-kpi-lbl { color: rgb(156,163,175); }

.db-kpi-val   { font-size: 1.5rem; font-weight: 700; line-height: 1.2;
                 margin-top: .25rem; font-variant-numeric: tabular-nums;
                 color: rgb(17,24,39); white-space: nowrap;
                 overflow: hidden; text-overflow: ellipsis; }
.dark .db-kpi-val { color: rgb(248,250,252); }

.db-kpi-sub   { display: flex; align-items: center; gap: .25rem;
                 font-size: .75rem; margin-top: .375rem; flex-wrap: wrap; }
.db-kpi-sub svg { width: .875rem; height: .875rem; flex-shrink: 0; }

/* ── Accent colours ───────────────────────────────────────── */
.cl-em  { color: rgb(5,150,105);  } .dark .cl-em  { color: rgb(52,211,153);  }
.cl-re  { color: rgb(220,38,38);  } .dark .cl-re  { color: rgb(248,113,113); }
.cl-am  { color: rgb(217,119,6);  } .dark .cl-am  { color: rgb(251,191,36);  }
.cl-bl  { color: rgb(37,99,235);  } .dark .cl-bl  { color: rgb(96,165,250);  }
.cl-vi  { color: rgb(124,58,237); } .dark .cl-vi  { color: rgb(167,139,250); }
.cl-cy  { color: rgb(8,145,178);  } .dark .cl-cy  { color: rgb(34,211,238);  }
.cl-gr  { color: rgb(107,114,128);} .dark .cl-gr  { color: rgb(156,163,175); }

.bg-em  { background: rgb(236,253,245); } .dark .bg-em  { background: rgba(52,211,153,.12); }
.bg-re  { background: rgb(254,242,242); } .dark .bg-re  { background: rgba(248,113,113,.12); }
.bg-am  { background: rgb(255,251,235); } .dark .bg-am  { background: rgba(251,191,36,.12); }
.bg-bl  { background: rgb(239,246,255); } .dark .bg-bl  { background: rgba(96,165,250,.12); }
.bg-vi  { background: rgb(245,243,255); } .dark .bg-vi  { background: rgba(167,139,250,.12); }
.bg-cy  { background: rgb(236,254,255); } .dark .bg-cy  { background: rgba(34,211,238,.12); }

/* ── Divider accent bar ───────────────────────────────────── */
.db-card-accent { border-left: 3px solid transparent; }
.db-card-em  { border-left-color: rgb(16,185,129); }
.db-card-re  { border-left-color: rgb(239,68,68);  }
.db-card-am  { border-left-color: rgb(245,158,11); }
.db-card-bl  { border-left-color: rgb(59,130,246); }
.db-card-vi  { border-left-color: rgb(139,92,246); }
.db-card-cy  { border-left-color: rgb(6,182,212);  }

/* ── Chart card ───────────────────────────────────────────── */
.db-chart-head   { font-size:.9375rem; font-weight:600; color:rgb(17,24,39); }
.dark .db-chart-head { color:rgb(248,250,252); }
.db-chart-sub    { font-size:.75rem; color:rgb(156,163,175); margin-top:.125rem; }
.db-chart-canvas { position:relative; margin-top:1rem; }
</style>

@php
    $pct = function (float $cur, float $prev): ?float {
        if ($prev == 0) return null;
        return round((($cur - $prev) / abs($prev)) * 100, 1);
    };
    $revPct = $pct($revenueCurrent, $revenuePrev);

    $monthLabels = [
        __('messages.jan'), __('messages.feb'), __('messages.mar'),
        __('messages.apr'), __('messages.may'), __('messages.jun'),
        __('messages.jul'), __('messages.aug'), __('messages.sep'),
        __('messages.oct'), __('messages.nov'), __('messages.dec'),
    ];

    $fmt = fn (float $v) => number_format($v, 0, '.', ' ');
@endphp

<div class="db-wrap">

    {{-- ══════════════════════════════════════════════════════════
         ROW 1 — COMMERCIAL KPIs
    ══════════════════════════════════════════════════════════ --}}
    <div class="db-section">
        <p class="db-section-lbl">{{ __('messages.commercial') }}</p>
        <div class="db-kpi-grid">

            {{-- Monthly Revenue --}}
            <div class="db-card db-card-accent db-card-em">
                <div class="db-kpi">
                    <div class="db-kpi-icon bg-em">
                        <x-heroicon-o-banknotes class="cl-em" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.monthly_revenue') }}</p>
                        <p class="db-kpi-val">MAD {{ $fmt($revenueCurrent) }}</p>
                        @if ($revPct !== null)
                            <p class="db-kpi-sub {{ $revPct >= 0 ? 'cl-em' : 'cl-re' }}">
                                @if ($revPct >= 0)
                                    <x-heroicon-m-arrow-trending-up />
                                @else
                                    <x-heroicon-m-arrow-trending-down />
                                @endif
                                {{ $revPct >= 0 ? '+' : '' }}{{ $revPct }}% {{ __('messages.vs_last_month') }}
                            </p>
                        @else
                            <p class="db-kpi-sub cl-gr">{{ __('messages.no_previous_data') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Unpaid Invoices --}}
            <div class="db-card db-card-accent {{ $unpaidAmount > 0 ? 'db-card-re' : 'db-card-em' }}">
                <div class="db-kpi">
                    <div class="db-kpi-icon {{ $unpaidAmount > 0 ? 'bg-re' : 'bg-em' }}">
                        <x-heroicon-o-credit-card class="{{ $unpaidAmount > 0 ? 'cl-re' : 'cl-em' }}" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.unpaid_invoices') }}</p>
                        <p class="db-kpi-val {{ $unpaidAmount > 0 ? 'cl-re' : '' }}">MAD {{ $fmt($unpaidAmount) }}</p>
                        <p class="db-kpi-sub {{ $unpaidCount > 0 ? 'cl-am' : 'cl-em' }}">
                            @if ($unpaidCount > 0)
                                <x-heroicon-m-exclamation-circle />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ $unpaidCount }} {{ __('messages.pending_customer_payments') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Reseller Debts --}}
            <div class="db-card db-card-accent {{ $resellerDebts > 0 ? 'db-card-am' : 'db-card-em' }}">
                <div class="db-kpi">
                    <div class="db-kpi-icon {{ $resellerDebts > 0 ? 'bg-am' : 'bg-em' }}">
                        <x-heroicon-o-building-storefront class="{{ $resellerDebts > 0 ? 'cl-am' : 'cl-em' }}" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.reseller_debts') }}</p>
                        <p class="db-kpi-val {{ $resellerDebts > 0 ? 'cl-am' : '' }}">MAD {{ $fmt($resellerDebts) }}</p>
                        <p class="db-kpi-sub {{ $resellerDebts > 0 ? 'cl-am' : 'cl-em' }}">
                            @if ($resellerDebts > 0)
                                <x-heroicon-m-exclamation-triangle />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ __('messages.total_reseller_unpaid') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Stock Valuation --}}
            <div class="db-card db-card-accent db-card-cy">
                <div class="db-kpi">
                    <div class="db-kpi-icon bg-cy">
                        <x-heroicon-o-cube class="cl-cy" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.stock_valuation') }}</p>
                        <p class="db-kpi-val">MAD {{ $fmt($stockValuation) }}</p>
                        <p class="db-kpi-sub cl-gr">
                            <x-heroicon-m-archive-box />
                            {{ __('messages.total_inventory_value') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         ROW 2 — WORKSHOP KPIs
    ══════════════════════════════════════════════════════════ --}}
    <div class="db-section">
        <p class="db-section-lbl">{{ __('messages.workshop') }}</p>
        <div class="db-kpi-grid">

            {{-- Active Repair Tickets --}}
            @php $activeTickets = $repairCounts[0] + $repairCounts[1] + $repairCounts[2] + $repairCounts[3]; @endphp
            <div class="db-card db-card-accent db-card-am">
                <div class="db-kpi">
                    <div class="db-kpi-icon bg-am">
                        <x-heroicon-o-wrench-screwdriver class="cl-am" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.active_tickets') }}</p>
                        <p class="db-kpi-val">{{ $activeTickets }}</p>
                        <p class="db-kpi-sub cl-am">
                            <x-heroicon-m-clock />
                            {{ __('messages.repairs_in_progress') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Completed Repairs --}}
            <div class="db-card db-card-accent db-card-em">
                <div class="db-kpi">
                    <div class="db-kpi-icon bg-em">
                        <x-heroicon-o-check-circle class="cl-em" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.completed_repairs') }}</p>
                        <p class="db-kpi-val">{{ $completedMonth }}</p>
                        <p class="db-kpi-sub cl-gr">
                            <x-heroicon-m-check-badge />
                            {{ $completedTotal }} {{ __('messages.total_completed_repairs') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Avg Repair Time --}}
            <div class="db-card db-card-accent db-card-vi">
                <div class="db-kpi">
                    <div class="db-kpi-icon bg-vi">
                        <x-heroicon-o-clock class="cl-vi" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.average_repair_time') }}</p>
                        <p class="db-kpi-val">{{ $avgHours }}<span style="font-size:1rem;font-weight:500;"> h</span></p>
                        <p class="db-kpi-sub cl-gr">
                            <x-heroicon-m-arrow-path />
                            {{ __('messages.average_completion_time') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Warranty Repairs --}}
            <div class="db-card db-card-accent {{ $warrantyOpen > 0 ? 'db-card-am' : 'db-card-em' }}">
                <div class="db-kpi">
                    <div class="db-kpi-icon {{ $warrantyOpen > 0 ? 'bg-am' : 'bg-em' }}">
                        <x-heroicon-o-shield-check class="{{ $warrantyOpen > 0 ? 'cl-am' : 'cl-em' }}" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.warranty_repairs') }}</p>
                        <p class="db-kpi-val">{{ $warrantyOpen }}</p>
                        <p class="db-kpi-sub {{ $warrantyOpen > 0 ? 'cl-am' : 'cl-em' }}">
                            @if ($warrantyOpen > 0)
                                <x-heroicon-m-exclamation-triangle />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ __('messages.repairs_under_warranty') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         ROW 3 — STOCK & TEAM (2 cards, fills first 2 cols)
    ══════════════════════════════════════════════════════════ --}}
    <div class="db-section">
        <p class="db-section-lbl">{{ __('messages.stock_management') }} &amp; {{ __('messages.team') }}</p>
        <div class="db-kpi-grid">

            {{-- Low Stock --}}
            <div class="db-card db-card-accent {{ $lowStockCount > 0 ? 'db-card-re' : 'db-card-em' }}">
                <div class="db-kpi">
                    <div class="db-kpi-icon {{ $lowStockCount > 0 ? 'bg-re' : 'bg-em' }}">
                        <x-heroicon-o-archive-box-x-mark class="{{ $lowStockCount > 0 ? 'cl-re' : 'cl-em' }}" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.low_stock') }}</p>
                        <p class="db-kpi-val {{ $lowStockCount > 0 ? 'cl-re' : '' }}">{{ $lowStockCount }}</p>
                        <p class="db-kpi-sub {{ $lowStockCount > 0 ? 'cl-re' : 'cl-em' }}">
                            @if ($lowStockCount > 0)
                                <x-heroicon-m-exclamation-triangle />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ __('messages.products_below_threshold') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Technicians --}}
            <div class="db-card db-card-accent db-card-bl">
                <div class="db-kpi">
                    <div class="db-kpi-icon bg-bl">
                        <x-heroicon-o-users class="cl-bl" />
                    </div>
                    <div class="db-kpi-body">
                        <p class="db-kpi-lbl">{{ __('messages.technicians') }}</p>
                        <p class="db-kpi-val">{{ $activeTech }}</p>
                        <p class="db-kpi-sub cl-gr">
                            <x-heroicon-m-identification />
                            {{ __('messages.active_workshop_staff') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         ROW 4 — CHARTS
    ══════════════════════════════════════════════════════════ --}}
    <div class="db-section">
        <p class="db-section-lbl">{{ __('messages.reports') }}</p>
        <div class="db-chart-grid">

            {{-- Revenue Line Chart --}}
            <div class="db-card">
                <p class="db-chart-head">{{ __('messages.monthly_revenue') }} — {{ $year }}</p>
                <p class="db-chart-sub">{{ __('messages.revenue') }}</p>
                <div class="db-chart-canvas" wire:ignore style="height:260px;">
                    <canvas id="db-rev-chart"></canvas>
                </div>
            </div>

            {{-- Repair Status Doughnut --}}
            <div class="db-card">
                <p class="db-chart-head">{{ __('messages.repair_status_analytics') }}</p>
                <p class="db-chart-sub">{{ __('messages.all_time') }}</p>
                <div class="db-chart-canvas" wire:ignore style="height:260px;">
                    <canvas id="db-rep-chart"></canvas>
                </div>
            </div>

        </div>
    </div>

</div>

@script
<script>
(function () {
    var rev    = @json($revenueByMonth);
    var rep    = @json($repairCounts);
    var months = @json($monthLabels);
    var repLbl = [
        @json(__('messages.open')),
        @json(__('messages.diagnostic')),
        @json(__('messages.assigned')),
        @json(__('messages.in_progress')),
        @json(__('messages.completed')),
        @json(__('messages.delivered')),
        @json(__('messages.cancelled')),
    ];

    ['db-rev-chart','db-rep-chart'].forEach(function(id){
        var c = Chart.getChart(id); if(c) c.destroy();
    });

    /* Revenue line */
    new Chart(document.getElementById('db-rev-chart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: @json(__('messages.revenue')),
                data: rev,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.45,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.85)',
                    titleFont: { size: 12 },
                    bodyFont: { size: 13 },
                    padding: 10,
                    callbacks: {
                        label: function(ctx){
                            return '  MAD ' + ctx.parsed.y.toLocaleString('fr-MA', {minimumFractionDigits:0});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    ticks: {
                        font: { size: 11 },
                        callback: function(v){ return 'MAD '+v.toLocaleString(); }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                },
            },
        },
    });

    /* Repair doughnut */
    new Chart(document.getElementById('db-rep-chart'), {
        type: 'doughnut',
        data: {
            labels: repLbl,
            datasets: [{
                data: rep,
                backgroundColor: ['#94a3b8','#f59e0b','#3b82f6','#8b5cf6','#10b981','#06b6d4','#ef4444'],
                borderColor: '#fff',
                borderWidth: 2.5,
                hoverOffset: 10,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyleWidth: 10,
                        padding: 14,
                        font: { size: 11 },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.85)',
                    padding: 10,
                }
            },
        },
    });
})();
</script>
@endscript

</x-filament-panels::page>
