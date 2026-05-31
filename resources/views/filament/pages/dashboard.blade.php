<x-filament-panels::page>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @endassets

    {{-- Dashboard-scoped CSS — Filament v5 uses Tailwind v4 (classes not in pre-built CSS injected here) --}}
    <style>
        .dash-grid-4{display:grid;grid-template-columns:repeat(1,1fr);gap:1rem;}
        @media(min-width:640px){.dash-grid-4{grid-template-columns:repeat(2,1fr);}}
        @media(min-width:1280px){.dash-grid-4{grid-template-columns:repeat(4,1fr);}}

        .dash-grid-charts{display:grid;grid-template-columns:1fr;gap:1.5rem;}
        @media(min-width:1024px){.dash-grid-charts{grid-template-columns:2fr 1fr;}}

        .dash-card{background:#fff;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 2px 0 rgba(0,0,0,.05);outline:1px solid rgba(9,9,11,.05);}
        .dark .dash-card{background:rgb(31,41,55);outline-color:rgba(255,255,255,.1);}

        .dash-section-label{font-size:.75rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgb(156,163,175);margin-bottom:.75rem;}
        .dark .dash-section-label{color:rgb(107,114,128);}

        .dash-kpi-label{font-size:.875rem;color:rgb(107,114,128);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .dark .dash-kpi-label{color:rgb(156,163,175);}

        .dash-kpi-value{font-size:1.5rem;font-weight:700;margin-top:.5rem;font-variant-numeric:tabular-nums;color:rgb(17,24,39);}
        .dark .dash-kpi-value{color:#fff;}

        .dash-kpi-sub{font-size:.75rem;margin-top:.25rem;display:flex;align-items:center;gap:.25rem;}

        .dash-icon-wrap{flex-shrink:0;border-radius:.5rem;padding:.625rem;display:flex;align-items:center;justify-content:center;}

        .dash-icon-wrap svg{width:1.25rem;height:1.25rem;flex-shrink:0;}
        .dash-kpi-sub svg{width:.875rem;height:.875rem;flex-shrink:0;}

        .dash-row-flex{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;}
        .dash-row-text{min-width:0;flex:1 1 0%;}

        .dash-chart-title{font-size:.875rem;font-weight:600;color:rgb(55,65,81);}
        .dark .dash-chart-title{color:rgb(229,231,235);}
        .dash-chart-sub{font-size:.75rem;color:rgb(156,163,175);margin-top:.125rem;margin-bottom:1rem;}

        .dash-section{margin-bottom:2rem;}

        .c-emerald{color:rgb(5,150,105);}.dark .c-emerald{color:rgb(52,211,153);}
        .c-red{color:rgb(239,68,68);}.dark .c-red{color:rgb(248,113,113);}
        .c-amber{color:rgb(245,158,11);}.dark .c-amber{color:rgb(251,191,36);}
        .c-blue{color:rgb(37,99,235);}.dark .c-blue{color:rgb(96,165,250);}
        .c-violet{color:rgb(124,58,237);}.dark .c-violet{color:rgb(167,139,250);}
        .c-cyan{color:rgb(8,145,178);}.dark .c-cyan{color:rgb(34,211,238);}
        .c-gray{color:rgb(107,114,128);}

        .bg-icon-emerald{background:rgb(236,253,245);}.dark .bg-icon-emerald{background:rgba(52,211,153,.1);}
        .bg-icon-red{background:rgb(254,242,242);}.dark .bg-icon-red{background:rgba(248,113,113,.1);}
        .bg-icon-amber{background:rgb(255,251,235);}.dark .bg-icon-amber{background:rgba(251,191,36,.1);}
        .bg-icon-blue{background:rgb(239,246,255);}.dark .bg-icon-blue{background:rgba(96,165,250,.1);}
        .bg-icon-violet{background:rgb(245,243,255);}.dark .bg-icon-violet{background:rgba(167,139,250,.1);}
        .bg-icon-cyan{background:rgb(236,254,255);}.dark .bg-icon-cyan{background:rgba(34,211,238,.1);}
    </style>

    @php
        $trend = function (float $current, float $prev): array {
            if ($prev == 0) return ['pct' => null, 'up' => true];
            $pct = round((($current - $prev) / abs($prev)) * 100, 1);
            return ['pct' => $pct, 'up' => $pct >= 0];
        };
        $revTrend = $trend($revenueCurrent, $revenuePrev);
        $monthLabels = [
            __('messages.jan'), __('messages.feb'), __('messages.mar'),
            __('messages.apr'), __('messages.may'), __('messages.jun'),
            __('messages.jul'), __('messages.aug'), __('messages.sep'),
            __('messages.oct'), __('messages.nov'), __('messages.dec'),
        ];
    @endphp

    {{-- ── Row 1: COMMERCIAL (Revenue · Unpaid · Reseller Debts · Stock Value) ── --}}
    <div class="dash-section">
        <p class="dash-section-label">{{ __('messages.commercial') }}</p>
        <div class="dash-grid-4">

            {{-- Monthly Revenue --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.monthly_revenue') }}</p>
                        <p class="dash-kpi-value">MAD {{ number_format($revenueCurrent, 0) }}</p>
                        @if ($revTrend['pct'] !== null)
                            <p class="dash-kpi-sub {{ $revTrend['up'] ? 'c-emerald' : 'c-red' }}">
                                @if ($revTrend['up'])
                                    <x-heroicon-m-arrow-trending-up />
                                @else
                                    <x-heroicon-m-arrow-trending-down />
                                @endif
                                {{ $revTrend['up'] ? '+' : '' }}{{ $revTrend['pct'] }}% {{ __('messages.vs_last_month') }}
                            </p>
                        @else
                            <p class="dash-kpi-sub c-gray">{{ __('messages.no_previous_data') }}</p>
                        @endif
                    </div>
                    <div class="dash-icon-wrap bg-icon-emerald">
                        <x-heroicon-o-banknotes class="c-emerald" />
                    </div>
                </div>
            </div>

            {{-- Unpaid Invoices --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.unpaid_invoices') }}</p>
                        <p class="dash-kpi-value {{ $unpaidAmount > 0 ? 'c-red' : '' }}">MAD {{ number_format($unpaidAmount, 0) }}</p>
                        <p class="dash-kpi-sub {{ $unpaidCount > 0 ? 'c-amber' : 'c-emerald' }}">
                            @if ($unpaidCount > 0)
                                <x-heroicon-m-exclamation-circle />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ $unpaidCount }} {{ __('messages.pending_customer_payments') }}
                        </p>
                    </div>
                    <div class="dash-icon-wrap {{ $unpaidAmount > 0 ? 'bg-icon-red' : 'bg-icon-emerald' }}">
                        <x-heroicon-o-credit-card class="{{ $unpaidAmount > 0 ? 'c-red' : 'c-emerald' }}" />
                    </div>
                </div>
            </div>

            {{-- Reseller Debts --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.reseller_debts') }}</p>
                        <p class="dash-kpi-value {{ $resellerDebts > 0 ? 'c-amber' : '' }}">MAD {{ number_format($resellerDebts, 0) }}</p>
                        <p class="dash-kpi-sub {{ $resellerDebts > 0 ? 'c-amber' : 'c-emerald' }}">
                            @if ($resellerDebts > 0)
                                <x-heroicon-m-exclamation-triangle />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ __('messages.total_reseller_unpaid') }}
                        </p>
                    </div>
                    <div class="dash-icon-wrap {{ $resellerDebts > 0 ? 'bg-icon-amber' : 'bg-icon-emerald' }}">
                        <x-heroicon-o-building-storefront class="{{ $resellerDebts > 0 ? 'c-amber' : 'c-emerald' }}" />
                    </div>
                </div>
            </div>

            {{-- Stock Valuation --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.stock_valuation') }}</p>
                        <p class="dash-kpi-value">MAD {{ number_format($stockValuation, 0) }}</p>
                        <p class="dash-kpi-sub c-gray">{{ __('messages.total_inventory_value') }}</p>
                    </div>
                    <div class="dash-icon-wrap bg-icon-cyan">
                        <x-heroicon-o-cube class="c-cyan" />
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Row 2: WORKSHOP (Active Tickets · Completed · Avg Time · Warranty) ─── --}}
    <div class="dash-section">
        <p class="dash-section-label">{{ __('messages.workshop') }}</p>
        <div class="dash-grid-4">

            {{-- Active Repair Tickets --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.active_tickets') }}</p>
                        <p class="dash-kpi-value">{{ $repairCounts[0] + $repairCounts[1] + $repairCounts[2] + $repairCounts[3] }}</p>
                        <p class="dash-kpi-sub c-amber">{{ __('messages.repairs_in_progress') }}</p>
                    </div>
                    <div class="dash-icon-wrap bg-icon-amber">
                        <x-heroicon-o-wrench-screwdriver class="c-amber" />
                    </div>
                </div>
            </div>

            {{-- Completed Repairs This Month --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.completed_repairs') }}</p>
                        <p class="dash-kpi-value">{{ $completedMonth }}</p>
                        <p class="dash-kpi-sub c-gray">{{ $completedTotal }} {{ __('messages.total_completed_repairs') }}</p>
                    </div>
                    <div class="dash-icon-wrap bg-icon-emerald">
                        <x-heroicon-o-check-circle class="c-emerald" />
                    </div>
                </div>
            </div>

            {{-- Average Repair Time --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.average_repair_time') }}</p>
                        <p class="dash-kpi-value">{{ $avgHours }} h</p>
                        <p class="dash-kpi-sub c-gray">{{ __('messages.average_completion_time') }}</p>
                    </div>
                    <div class="dash-icon-wrap bg-icon-violet">
                        <x-heroicon-o-clock class="c-violet" />
                    </div>
                </div>
            </div>

            {{-- Warranty Repairs Open --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.warranty_repairs') }}</p>
                        <p class="dash-kpi-value">{{ $warrantyOpen }}</p>
                        <p class="dash-kpi-sub {{ $warrantyOpen > 0 ? 'c-amber' : 'c-emerald' }}">
                            @if ($warrantyOpen > 0)
                                <x-heroicon-m-shield-check />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ __('messages.repairs_under_warranty') }}
                        </p>
                    </div>
                    <div class="dash-icon-wrap {{ $warrantyOpen > 0 ? 'bg-icon-amber' : 'bg-icon-emerald' }}">
                        <x-heroicon-o-shield-check class="{{ $warrantyOpen > 0 ? 'c-amber' : 'c-emerald' }}" />
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Row 3: INVENTORY & TEAM (Low Stock · Technicians) ───────────────── --}}
    <div class="dash-section">
        <p class="dash-section-label">{{ __('messages.stock_management') }} &amp; {{ __('messages.team') }}</p>
        <div class="dash-grid-4">

            {{-- Low Stock Products --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.low_stock') }}</p>
                        <p class="dash-kpi-value {{ $lowStockCount > 0 ? 'c-red' : '' }}">{{ $lowStockCount }}</p>
                        <p class="dash-kpi-sub {{ $lowStockCount > 0 ? 'c-red' : 'c-emerald' }}">
                            @if ($lowStockCount > 0)
                                <x-heroicon-m-exclamation-triangle />
                            @else
                                <x-heroicon-m-check-circle />
                            @endif
                            {{ __('messages.products_below_threshold') }}
                        </p>
                    </div>
                    <div class="dash-icon-wrap {{ $lowStockCount > 0 ? 'bg-icon-red' : 'bg-icon-emerald' }}">
                        <x-heroicon-o-archive-box-x-mark class="{{ $lowStockCount > 0 ? 'c-red' : 'c-emerald' }}" />
                    </div>
                </div>
            </div>

            {{-- Active Technicians --}}
            <div class="dash-card">
                <div class="dash-row-flex">
                    <div class="dash-row-text">
                        <p class="dash-kpi-label">{{ __('messages.technicians') }}</p>
                        <p class="dash-kpi-value">{{ $activeTech }}</p>
                        <p class="dash-kpi-sub c-gray">{{ __('messages.active_workshop_staff') }}</p>
                    </div>
                    <div class="dash-icon-wrap bg-icon-blue">
                        <x-heroicon-o-users class="c-blue" />
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Charts ────────────────────────────────────────────────────────────── --}}
    <div class="dash-section">
        <div class="dash-grid-charts">

            {{-- Monthly Revenue line chart --}}
            <div class="dash-card">
                <p class="dash-chart-title">{{ __('messages.monthly_revenue') }} — {{ $year }}</p>
                <p class="dash-chart-sub">{{ __('messages.revenue') }}</p>
                <div wire:ignore style="position:relative;height:260px;">
                    <canvas id="dash-financial-chart"></canvas>
                </div>
            </div>

            {{-- Repair Status doughnut --}}
            <div class="dash-card">
                <p class="dash-chart-title">{{ __('messages.repair_status_analytics') }}</p>
                <p class="dash-chart-sub">{{ __('messages.all_time') }}</p>
                <div wire:ignore style="position:relative;height:260px;">
                    <canvas id="dash-repair-chart"></canvas>
                </div>
            </div>

        </div>
    </div>

    @script
    <script>
        (function () {
            var revenueData  = @json($revenueByMonth);
            var repairData   = @json($repairCounts);
            var monthLabels  = @json($monthLabels);
            var repairLabels = [
                @json(__('messages.open')),
                @json(__('messages.diagnostic')),
                @json(__('messages.assigned')),
                @json(__('messages.in_progress')),
                @json(__('messages.completed')),
                @json(__('messages.delivered')),
                @json(__('messages.cancelled')),
            ];

            ['dash-financial-chart', 'dash-repair-chart'].forEach(function (id) {
                var existing = Chart.getChart(id);
                if (existing) existing.destroy();
            });

            new Chart(document.getElementById('dash-financial-chart'), {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: @json(__('messages.revenue')),
                        data: revenueData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.10)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#10b981',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ' ' + ctx.dataset.label + ': MAD ' + ctx.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: { callback: function (v) { return 'MAD ' + v.toLocaleString(); } }
                        },
                        x: { grid: { display: false } },
                    },
                },
            });

            new Chart(document.getElementById('dash-repair-chart'), {
                type: 'doughnut',
                data: {
                    labels: repairLabels,
                    datasets: [{
                        data: repairData,
                        backgroundColor: ['#94a3b8','#f59e0b','#3b82f6','#8b5cf6','#10b981','#06b6d4','#ef4444'],
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, padding: 12, font: { size: 11 } }
                        },
                    },
                },
            });
        })();
    </script>
    @endscript

</x-filament-panels::page>
