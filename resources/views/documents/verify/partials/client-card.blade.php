{{--
    Reusable client information card.

    Variables (pass via @include's second argument):
        $label     - (optional) card heading; defaults to messages.client
        $name      - display name (company name, full name, etc.)
        $type      - 'person' | 'company' | 'administration'
        $ice       - (optional) ICE number
        $cin       - (optional) CIN or passport number (whichever the client uses)
        $cinLabel  - (optional) label for $cin; defaults to 'CIN'
        $phone     - (optional) phone number
        $address   - (optional) address line
--}}
<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
        {{ $label ?? __('messages.client') }}
    </div>
    <div class="text-base font-bold leading-snug text-slate-900">
        {{ $name ?: '—' }}
    </div>
    @if(in_array($type ?? 'person', ['company', 'administration']))
        @if($ice ?? null)
            <div class="mt-1 text-sm text-slate-600">ICE: {{ $ice }}</div>
        @endif
        @if($phone ?? null)
            <div class="text-sm text-slate-600">{{ __('messages.phone') }}: {{ $phone }}</div>
        @endif
    @else
        @if($cin ?? null)
            <div class="mt-1 text-sm text-slate-600">{{ $cinLabel ?? 'CIN' }}: {{ $cin }}</div>
        @endif
        @if($phone ?? null)
            <div class="text-sm text-slate-600">{{ __('messages.phone') }}: {{ $phone }}</div>
        @endif
    @endif
    @if($address ?? null)
        <div class="mt-1 text-sm leading-snug text-slate-400">{{ $address }}</div>
    @endif
</div>
