{{--
    Company information card.

    Variables (pass via @include's second argument):
        $name     - company name
        $logo     - (optional) logo file path relative to storage (e.g. 'logos/abc.png')
        $address  - (optional) address
        $phone    - (optional) phone
        $email    - (optional) email
        $ice      - (optional) ICE number
        $rc       - (optional) RC number
        $cardLabel - (optional) card heading; defaults to messages.company
--}}
<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
        {{ $cardLabel ?? __('messages.company') }}
    </div>

    <div class="flex items-start gap-3">
        @php
            $logoAbsPath = ($logo ?? null) ? public_path('storage/' . $logo) : null;
        @endphp
        @if($logoAbsPath && file_exists($logoAbsPath))
            <img src="{{ asset('storage/' . $logo) }}"
                 alt="{{ $name }}"
                 class="h-10 w-auto flex-shrink-0 rounded object-contain">
        @endif

        <div class="min-w-0">
            <div class="font-bold leading-snug text-slate-900">{{ $name ?: '—' }}</div>

            @if($address ?? null)
                <div class="mt-1 text-sm leading-snug text-slate-500">{{ $address }}</div>
            @endif

            @if($phone ?? null)
                <div class="text-sm text-slate-500">{{ __('messages.phone') }}: {{ $phone }}</div>
            @endif

            @if($email ?? null)
                <div class="text-sm text-slate-500">{{ $email }}</div>
            @endif

            @if(($ice ?? null) || ($rc ?? null))
                <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-slate-400">
                    @if($ice ?? null)<span>ICE: {{ $ice }}</span>@endif
                    @if($rc ?? null)<span>RC: {{ $rc }}</span>@endif
                </div>
            @endif
        </div>
    </div>
</div>
