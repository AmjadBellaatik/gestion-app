{{-- Galaxi — professional branded restyle of the auth (login / reset) pages.
     Pure CSS, scoped to Filament's `.fi-simple-*` auth layout — no change to
     any auth logic or form behaviour. --}}
<style>
    .fi-simple-layout {
        background:
            radial-gradient(1100px 560px at 12% -12%, rgba(237, 29, 36, 0.22), transparent 60%),
            radial-gradient(900px 480px at 112% 116%, rgba(245, 158, 11, 0.16), transparent 55%),
            linear-gradient(135deg, #0b1220 0%, #172033 55%, #0b1220 100%);
        min-height: 100vh;
    }

    /* Subtle diagonal texture behind the card */
    .fi-simple-layout::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background-image: repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.025) 0 1px, transparent 1px 24px);
    }

    /* The card */
    .fi-simple-main {
        position: relative;
        z-index: 1;
        border-radius: 18px !important;
        box-shadow: 0 28px 70px -18px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
        padding: 2.4rem 2.1rem !important;
        width: 100%;
        max-width: 27rem;
    }

    /* Filament's default simple header is empty here (no brand set); our own
       branded heading is injected inside the card instead. */
    .fi-simple-header { display: none !important; }

    /* Inputs + primary button polish */
    .fi-simple-main .fi-input-wrp { border-radius: 11px; }
    .fi-simple-main .fi-btn { border-radius: 11px; font-weight: 700; letter-spacing: .01em; }

    /* Accent bar on top of the card */
    .fi-simple-main::before {
        content: '';
        display: block;
        height: 4px;
        width: 56px;
        margin: 0 auto 1.4rem;
        border-radius: 99px;
        background: linear-gradient(90deg, #ed1d24, #f59e0b);
    }
</style>
