@extends('documents.verify.layouts.master')

@section('page-title', ($document->documentType?->name ?? __('messages.document')) . ' — ' . $document->document_number)

@section('content')

{{-- ── Parties ────────────────────────────────────────────────────────── --}}
<div class="grid gap-4 sm:grid-cols-2">

    @include('documents.verify.partials.company-card', [
        'name'    => $document->company?->name,
        'address' => $document->company?->address,
        'phone'   => $document->company?->phone,
        'logo'    => $document->company?->logo,
    ])

    @if($document->client)
        @include('documents.verify.partials.client-card', [
            'name'  => $v->clientName(),
            'type'  => $v->clientType(),
            'ice'   => $v->clientIce(),
            'cin'      => $v->clientCin(),
            'cinLabel' => $v->clientIdentityLabel(),
            'phone'    => $v->clientPhone(),
        ])
    @endif

</div>

{{-- ── Items ──────────────────────────────────────────────────────────── --}}
@if($document->items->isNotEmpty())
    <div class="mt-6">
        @include('documents.verify.partials.items-table', [
            'items'        => $document->items,
            'showDiscount' => true,
            'showTaxRate'  => false,
        ])
    </div>

    @include('documents.verify.partials.totals')
@endif

{{-- ── Notes ───────────────────────────────────────────────────────────── --}}
@if($document->notes)
    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('messages.notes') }}</div>
        <p class="text-sm text-slate-700">{{ $document->notes }}</p>
    </div>
@endif

@endsection
