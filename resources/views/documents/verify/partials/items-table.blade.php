{{--
    Reusable document items table.

    Variables:
        $items          - Collection<DocumentItem>
        $showDiscount   - (bool, default true)  show the discount column
        $showTaxRate    - (bool, default false) show the tax-rate column
--}}
@php
    $showDiscount = $showDiscount ?? true;
    $showTaxRate  = $showTaxRate  ?? false;
    $colCount     = 4 + ($showDiscount ? 1 : 0) + ($showTaxRate ? 1 : 0);
@endphp

<div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-800 text-white">
            <tr>
                <th class="px-4 py-3 text-left font-medium">{{ __('messages.description') }}</th>
                <th class="px-4 py-3 text-right font-medium">{{ __('messages.quantity') }}</th>
                <th class="px-4 py-3 text-right font-medium">{{ __('messages.unit_price_ttc') }}</th>
                @if($showDiscount)
                    <th class="px-4 py-3 text-right font-medium">{{ __('messages.fixed_discount') }}</th>
                @endif
                @if($showTaxRate)
                    <th class="px-4 py-3 text-right font-medium">{{ __('messages.tax_rate') }}</th>
                @endif
                <th class="px-4 py-3 text-right font-medium">{{ __('messages.total_amount') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse($items as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $item->description }}</div>
                        @if($item->motorcycleUnit)
                            <div class="mt-0.5 text-xs text-slate-500">
                                {{ __('messages.chassis_number') }}: {{ $item->motorcycleUnit->chassis_number }}
                            </div>
                        @endif
                        @if($item->line_notes)
                            <div class="mt-0.5 text-xs italic text-slate-400">{{ $item->line_notes }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-slate-700">
                        {{ number_format((float) $item->quantity, 2, ',', ' ') }}
                    </td>
                    <td class="px-4 py-3 text-right text-slate-700">
                        {{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD
                    </td>
                    @if($showDiscount)
                        <td class="px-4 py-3 text-right text-slate-700">
                            {{ number_format((float) $item->discount_amount, 2, ',', ' ') }} MAD
                        </td>
                    @endif
                    @if($showTaxRate)
                        <td class="px-4 py-3 text-right text-slate-500">20%</td>
                    @endif
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                        {{ number_format((float) $item->total, 2, ',', ' ') }} MAD
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colCount }}" class="px-4 py-8 text-center italic text-slate-400">—</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
