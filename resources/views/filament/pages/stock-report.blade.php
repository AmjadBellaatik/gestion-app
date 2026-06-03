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
                    <div class="rpt-kpi-icon bg-bl"><x-heroicon-o-archive-box class="cl-bl"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.total_products') }}</p>
                        <p class="rpt-kpi-val">{{ $totalProducts }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card {{ $lowStockCount > 0 ? 'rpt-card-re' : 'rpt-card-em' }}">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon {{ $lowStockCount > 0 ? 'bg-re' : 'bg-em' }}"><x-heroicon-o-archive-box-x-mark class="{{ $lowStockCount > 0 ? 'cl-re' : 'cl-em' }}"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.low_stock') }}</p>
                        <p class="rpt-kpi-val {{ $lowStockCount > 0 ? 'cl-re' : '' }}">{{ $lowStockCount }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-cy">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-cy"><x-heroicon-o-currency-dollar class="cl-cy"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.stock_valuation') }}</p>
                        <p class="rpt-kpi-val">MAD {{ $fmt($totalValue) }}</p>
                    </div>
                </div>
            </div>
            <div class="rpt-card rpt-card-vi">
                <div class="rpt-kpi">
                    <div class="rpt-kpi-icon bg-vi"><x-heroicon-o-arrow-path class="cl-vi"/></div>
                    <div class="rpt-kpi-body">
                        <p class="rpt-kpi-lbl">{{ __('messages.movements_in_period') }}</p>
                        <p class="rpt-kpi-val">{{ $movementsCount }}</p>
                        <p class="rpt-kpi-sub">↑ {{ $entriesCount }} {{ __('messages.in') }} &nbsp; ↓ {{ $exitsCount }} {{ __('messages.out') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.products_inventory') }}</p>
        @if ($products->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_data_for_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.product') }}</th>
                        <th>{{ __('messages.sku') }}</th>
                        <th>{{ __('messages.current_stock') }}</th>
                        <th>{{ __('messages.alert_threshold') }}</th>
                        <th>{{ __('messages.purchase_price') }}</th>
                        <th>{{ __('messages.selling_price') }}</th>
                        <th>{{ __('messages.stock_value') }}</th>
                        <th>{{ __('messages.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                    <tr>
                        <td style="font-weight:600;">{{ $product->name }}</td>
                        <td style="color:rgb(107,114,128);">{{ $product->sku ?? '—' }}</td>
                        <td style="font-weight:600;">{{ number_format($product->current_qty, 2) }}</td>
                        <td>{{ $product->stock_alert > 0 ? $product->stock_alert : '—' }}</td>
                        <td>{{ $product->purchase_price ? 'MAD '.$fmt($product->purchase_price) : '—' }}</td>
                        <td>MAD {{ $fmt($product->selling_price) }}</td>
                        <td style="font-weight:600;">MAD {{ $fmt($product->stock_value) }}</td>
                        <td>
                            @if ($product->is_low)
                                <span class="rpt-badge rpt-badge-re">{{ __('messages.low_stock') }}</span>
                            @elseif ($product->current_qty <= 0)
                                <span class="rpt-badge rpt-badge-am">{{ __('messages.out_of_stock') }}</span>
                            @else
                                <span class="rpt-badge rpt-badge-em">{{ __('messages.in_stock') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Movements Table --}}
    <div class="rpt-card">
        <p class="rpt-section-lbl" style="margin-bottom:1rem;">{{ __('messages.stock_movements') }} — {{ $periodLabel }}</p>
        @if ($movements->isEmpty())
            <p class="rpt-empty">{{ __('messages.no_movements_in_period') }}</p>
        @else
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.product') }}</th>
                        <th>{{ __('messages.type') }}</th>
                        <th>{{ __('messages.quantity') }}</th>
                        <th>{{ __('messages.unit_cost') }}</th>
                        <th>{{ __('messages.reference') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movements as $mv)
                    <tr>
                        <td>{{ $mv->created_at->format('d/m/Y') }}</td>
                        <td>{{ $mv->product?->name ?? '—' }}</td>
                        <td>
                            <span class="rpt-badge {{ in_array($mv->type, ['entry','in']) ? 'rpt-badge-em' : 'rpt-badge-re' }}">
                                {{ __('messages.'.$mv->type) }}
                            </span>
                        </td>
                        <td style="font-weight:600;">{{ number_format($mv->quantity, 2) }}</td>
                        <td>{{ $mv->unit_cost ? 'MAD '.$fmt($mv->unit_cost) : '—' }}</td>
                        <td style="color:rgb(107,114,128);">{{ $mv->reference ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
