{{--
    Reusable financial totals summary (HT / TVA 20% / TTC).

    Uses $v (DocumentVerificationPresenter) from parent scope.
    All calculations live in the presenter — this partial is display-only.
--}}
<div class="mt-4 grid gap-3 sm:grid-cols-3">

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ __('messages.subtotal_ht') }}
        </div>
        <div class="mt-1 text-lg font-bold text-slate-900">
            {{ number_format($v->subtotal(), 2, ',', ' ') }} MAD
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ __('messages.tva_20') }}
        </div>
        <div class="mt-1 text-lg font-bold text-slate-900">
            {{ number_format($v->taxAmount(), 2, ',', ' ') }} MAD
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 ring-1 ring-slate-300">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ __('messages.total_ttc') }}
        </div>
        <div class="mt-1 text-xl font-bold text-slate-900">
            {{ number_format($v->totalTtc(), 2, ',', ' ') }} MAD
        </div>
    </div>

</div>
