@extends('documents.verify.layouts.master')

@section('page-title', __('messages.supplier_order') . ' — ' . $document->document_number)

@section('content')

{{-- ── Company (buyer) + Supplier (seller) ─────────────────────────── --}}
<div class="grid gap-4 sm:grid-cols-2">

    @include('documents.verify.partials.company-card', [
        'name'      => $document->company?->name,
        'address'   => $document->company?->address ?? $document->company?->legal_address,
        'phone'     => $document->company?->phone,
        'logo'      => $document->company?->logo,
        'cardLabel' => __('messages.delivery_address'),
    ])

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            {{ __('messages.provider') }}
        </div>
        <div class="font-bold text-slate-900">{{ $v->supplierName() ?: '—' }}</div>
        @if($v->supplierAddress())
            <div class="mt-1 text-sm text-slate-500">{{ $v->supplierAddress() }}</div>
        @endif
        @if($v->supplierPhone())
            <div class="text-sm text-slate-500">{{ __('messages.phone') }}: {{ $v->supplierPhone() }}</div>
        @endif
        @if($v->supplierEmail())
            <div class="text-sm text-slate-500">{{ $v->supplierEmail() }}</div>
        @endif
        @if($v->supplierIce() || $v->supplierRc())
            <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-slate-400">
                @if($v->supplierIce())<span>ICE: {{ $v->supplierIce() }}</span>@endif
                @if($v->supplierRc())<span>RC: {{ $v->supplierRc() }}</span>@endif
            </div>
        @endif
    </div>

</div>

{{-- ── Supplier quote reference ──────────────────────────────────────── --}}
@if($v->supplierQuoteReference())
    <div class="mt-4 flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <span class="font-semibold">{{ __('messages.provider_quote_reference') }}:</span>
        <span>{{ $v->supplierQuoteReference() }}</span>
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

@endsection
