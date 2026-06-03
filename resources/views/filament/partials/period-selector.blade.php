@php
    $periods = [
        'today'         => __('messages.period_today'),
        'yesterday'     => __('messages.period_yesterday'),
        'this_week'     => __('messages.period_this_week'),
        'this_month'    => __('messages.period_this_month'),
        'last_month'    => __('messages.period_last_month'),
        'last_3_months' => __('messages.period_last_3_months'),
        'this_year'     => __('messages.period_this_year'),
        'custom'        => __('messages.period_custom'),
    ];
@endphp

<div class="rpt-period-bar">
    <div class="rpt-period-presets">
        @foreach ($periods as $key => $label)
            <button
                class="rpt-period-btn {{ $this->period === $key ? 'active' : '' }}"
                wire:click="setPeriod('{{ $key }}')"
            >{{ $label }}</button>
        @endforeach
    </div>
    <span class="rpt-period-label">
        <x-heroicon-m-calendar style="display:inline-block;width:.875rem;height:.875rem;vertical-align:-.15em;margin-right:.25rem;" />
        {{ $periodLabel }}
    </span>

    @if ($this->period === 'custom')
    <div class="rpt-custom-range">
        <label>{{ __('messages.from_date') }}</label>
        <input type="date" wire:model.live="dateFrom" value="{{ $this->dateFrom }}">
        <label>{{ __('messages.to_date') }}</label>
        <input type="date" wire:model.live="dateTo"   value="{{ $this->dateTo }}">
    </div>
    @endif
</div>
