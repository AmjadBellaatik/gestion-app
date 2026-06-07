{{--
    Document identity card — rendered between the page header and the main
    content area for every authentic document.

    Uses from parent scope:
        $document  — the Document model (always present when authentic)
        $v         — DocumentVerificationPresenter (always present when authentic)
--}}
<div class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-5xl px-4 py-4 sm:px-6">

        {{-- ── Document type + number + date + status badge ─────────────── --}}
        <div class="flex flex-wrap items-start justify-between gap-3">

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">
                        {{ $document->documentType?->name }}
                    </h1>
                    @include('documents.verify.partials.status-badge', $v->statusBadge())
                </div>

                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm">
                    <span class="font-semibold text-slate-700">
                        {{ $document->document_number }}
                    </span>
                    <span class="text-slate-400">
                        {{ $document->document_date?->format('d/m/Y') }}
                    </span>
                </div>
            </div>

        </div>

        {{-- ── Quick summary: Company → Counterparty ─────────────────────── --}}
        <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-500">

            @if($document->company?->name)
                <div>
                    <span class="font-semibold uppercase tracking-wide text-slate-400">
                        {{ __('messages.company') }} :
                    </span>
                    {{ $document->company->name }}
                </div>
            @endif

            @if($v->counterpartyName())
                <div>
                    <span class="font-semibold uppercase tracking-wide text-slate-400">
                        {{ $v->counterpartyLabel() }} :
                    </span>
                    {{ $v->counterpartyName() }}
                </div>
            @endif

        </div>

    </div>
</div>
