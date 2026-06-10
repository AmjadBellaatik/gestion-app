<!DOCTYPE html>
@php $lang = $document?->language ?? app()->getLocale() ?? 'fr'; @endphp
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('page-title', __('messages.document_verification'))</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">

    {{-- ════════════════════════════════════════════════════════════════════
         VERIFICATION PAGE HEADER
         Always rendered. Shows company identity on the left and the
         verification status badge (green / red) on the right.
         Degrades gracefully when $document is null (not-found case).
    ════════════════════════════════════════════════════════════════════ --}}
    @php $company = $document?->company; @endphp

    <header class="sticky top-0 z-10 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto max-w-5xl px-4 py-3 sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                {{-- Company Identity --}}
                <div class="flex min-w-0 items-center gap-3">
                    @php
                        $logoPath = $company?->logo ? public_path('storage/' . $company->logo) : null;
                    @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ asset('storage/' . $company->logo) }}"
                             alt="{{ $company->name }}"
                             class="h-9 w-auto flex-shrink-0 object-contain">
                    @endif
                    <div class="min-w-0">
                        <div class="truncate text-base font-bold leading-tight text-slate-900 sm:text-lg">
                            {{ $company?->name ?? config('app.name') }}
                        </div>
                        <div class="text-xs font-medium uppercase tracking-wider text-slate-400">
                            {{ __('messages.verification_portal') }}
                        </div>
                    </div>
                </div>

                {{-- Verification Status Badge --}}
                @if($authentic)
                    <div class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-4 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">
                        <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('messages.authentic_document') }}
                    </div>
                @else
                    <div class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-red-50 px-4 py-1.5 text-sm font-semibold text-red-700 ring-1 ring-red-200">
                        <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('messages.not_authentic_document') }}
                    </div>
                @endif

            </div>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════════════════
         DOCUMENT IDENTITY BAR
         Shown only when the document is authentic. Delegates to the
         document-identity partial which uses the $v presenter for status.
    ════════════════════════════════════════════════════════════════════ --}}
    @if($authentic && $document)
        @include('documents.verify.partials.document-identity')
    @endif

    {{-- ════════════════════════════════════════════════════════════════════
         MAIN CONTENT ZONE
         Rendered by child templates via @section('content').
    ════════════════════════════════════════════════════════════════════ --}}
    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
        @yield('content')
    </main>

    {{-- ════════════════════════════════════════════════════════════════════
         VERIFICATION FOOTER
         Shown only for authentic documents. Displays the canonical
         verification URL and the document UUID for traceability.
    ════════════════════════════════════════════════════════════════════ --}}
    @if($authentic && $document)
        <footer class="border-t border-slate-200 bg-white py-4">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="flex flex-col gap-1 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-1.5 break-all">
                        <svg class="h-3 w-3 flex-shrink-0 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $document->verification_url }}
                    </div>
                    <div class="shrink-0 font-mono tracking-tight">
                        {{ $document->uuid }}
                    </div>
                </div>
            </div>
        </footer>
    @endif

</body>
</html>
