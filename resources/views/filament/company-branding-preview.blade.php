@php
    /** @var string|null $logoUrl */
    /** @var string $primary */
    /** @var string $secondary */
    /** @var string $accent */
@endphp

<div style="border:1px solid rgba(0,0,0,.08);border-radius:.75rem;overflow:hidden;max-width:34rem;font-family:ui-sans-serif,system-ui,sans-serif;">

    {{-- Mini application header --}}
    <div style="display:flex;align-items:center;gap:.625rem;padding:.625rem .875rem;background:{{ $primary }};color:#fff;">
        <span style="display:inline-flex;width:1.75rem;height:1.75rem;border-radius:.375rem;background:rgba(255,255,255,.18);align-items:center;justify-content:center;overflow:hidden;flex:0 0 1.75rem;">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" style="width:100%;height:100%;object-fit:contain;">
            @endif
        </span>
        <span style="font-weight:600;font-size:.8125rem;letter-spacing:.02em;">{{ __('messages.visual_identity_preview') }}</span>
    </div>

    {{-- Body: swatches + sample controls --}}
    <div style="padding:.875rem;background:#fff;">
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:.875rem;">
            @foreach ([
                'primary' => [$primary, __('messages.primary_color')],
                'secondary' => [$secondary, __('messages.secondary_color')],
                'accent' => [$accent, __('messages.accent_color')],
            ] as [$hex, $label])
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <span style="width:1.25rem;height:1.25rem;border-radius:.3125rem;border:1px solid rgba(0,0,0,.12);background:{{ $hex }};"></span>
                    <span style="font-size:.75rem;color:#374151;">{{ $label }}<br><code style="font-size:.6875rem;color:#6b7280;">{{ $hex }}</code></span>
                </div>
            @endforeach
        </div>

        <div aria-hidden="true" style="display:flex;align-items:center;gap:.625rem;flex-wrap:wrap;">
            <span style="display:inline-block;border-radius:.5rem;padding:.4375rem .875rem;font-size:.75rem;font-weight:600;color:#fff;background:{{ $primary }};">
                {{ __('messages.save') }}
            </span>
            <span style="display:inline-block;border-radius:9999px;padding:.1875rem .625rem;font-size:.6875rem;font-weight:600;background:{{ $primary }}29;color:{{ $secondary }};">
                {{ __('messages.branding') }}
            </span>
            <span style="width:2rem;height:.375rem;border-radius:9999px;background:{{ $accent }};"></span>
        </div>
    </div>
</div>
