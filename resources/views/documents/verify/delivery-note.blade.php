@extends('documents.verify.layouts.master')

@section('page-title', __('messages.delivery_note') . ' — ' . $document->document_number)

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

{{-- ── Reference (sale or repair ticket) ────────────────────────────── --}}
@if($document->sale)
    <div class="mt-4 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <span class="font-semibold">{{ __('messages.sale') }}:</span>
        <span>{{ $document->sale->sale_number }}</span>
    </div>
@elseif($document->repairTicket)
    <div class="mt-4 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <span class="font-semibold">{{ __('messages.repair_ticket') }}:</span>
        <span>{{ $document->repairTicket->ticket_number }}</span>
    </div>
@endif

{{-- ── Items ──────────────────────────────────────────────────────────── --}}
<div class="mt-6">
    @include('documents.verify.partials.items-table', [
        'items'        => $document->items,
        'showDiscount' => false,
        'showTaxRate'  => false,
    ])
</div>

{{-- ── Totals ─────────────────────────────────────────────────────────── --}}
@include('documents.verify.partials.totals')

@endsection
