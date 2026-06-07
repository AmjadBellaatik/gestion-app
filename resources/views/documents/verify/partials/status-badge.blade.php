{{--
    Document status badge.

    Variables:
        $label  - human-readable status string
        $color  - 'emerald' | 'blue' | 'amber' | 'red' | 'purple' | 'slate'

    Usage:
        @include('documents.verify.partials.status-badge', $v->statusBadge())
--}}
@php
    $badgeClasses = match ($color ?? 'slate') {
        'emerald' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'blue'    => 'bg-blue-100 text-blue-800 ring-blue-200',
        'amber'   => 'bg-amber-100 text-amber-800 ring-amber-200',
        'red'     => 'bg-red-100 text-red-800 ring-red-200',
        'purple'  => 'bg-purple-100 text-purple-800 ring-purple-200',
        default   => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClasses }}">
    {{ $label ?? '' }}
</span>
