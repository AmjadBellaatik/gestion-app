@extends('documents.verify.layouts.master')

@section('page-title', __('messages.sale_return') . ' — ' . $document->document_number)

@section('content')

{{-- ── Parties ────────────────────────────────────────────────────────── --}}
<div class="grid gap-4 sm:grid-cols-2">

    @include('documents.verify.partials.company-card', [
        'name'    => $document->company?->name,
        'address' => $document->company?->address,
        'phone'   => $document->company?->phone,
        'logo'    => $document->company?->logo,
    ])

    @include('documents.verify.partials.client-card', [
        'name'  => $v->clientName(),
        'type'  => $v->clientType(),
        'ice'   => $v->clientIce(),
        'cin'   => $v->clientCin(),
        'phone' => $v->clientPhone(),
    ])

</div>

{{-- ── Sale reference ────────────────────────────────────────────────── --}}
@if($document->sale)
    <div class="mt-4 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <span class="font-semibold">{{ __('messages.sale') }}:</span>
        <span>{{ $document->sale->sale_number }}</span>
    </div>
@endif

{{-- ── Items ──────────────────────────────────────────────────────────── --}}
<div class="mt-6">
    @include('documents.verify.partials.items-table', [
        'items'        => $document->items,
        'showDiscount' => false,
        'showTaxRate'  => true,
    ])
</div>

{{-- ── Totals ─────────────────────────────────────────────────────────── --}}
@include('documents.verify.partials.totals')

{{-- ── Return reason ────────────────────────────────────────────────── --}}
@if($document->notes)
    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-amber-700">
            {{ __('messages.return_reason') }}
        </div>
        <p class="text-sm text-amber-900">{{ $document->notes }}</p>
    </div>
@endif

@endsection
