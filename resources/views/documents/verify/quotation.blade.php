@extends('documents.verify.layouts.master')

@section('page-title', ($document->documentType?->name ?? __('messages.quotation')) . ' — ' . $document->document_number)

@section('content')

{{-- ── Parties ────────────────────────────────────────────────────────── --}}
<div class="grid gap-4 sm:grid-cols-2">

    @include('documents.verify.partials.company-card', [
        'name'    => $document->company?->name,
        'address' => $document->company?->address,
        'phone'   => $document->company?->phone,
        'ice'     => $document->company?->ice,
        'logo'    => $document->company?->logo,
    ])

    @include('documents.verify.partials.client-card', [
        'name'  => $v->displayClientName(),
        'type'  => $v->displayClientType(),
        'ice'   => $v->displayClientIce(),
        'cin'      => $v->displayClientCin(),
        'cinLabel' => $v->displayClientIdentityLabel(),
        'phone'    => $v->displayClientPhone(),
    ])

</div>

{{-- ── Items ──────────────────────────────────────────────────────────── --}}
<div class="mt-6">
    @include('documents.verify.partials.items-table', [
        'items'        => $document->items,
        'showDiscount' => true,
        'showTaxRate'  => false,
    ])
</div>

{{-- ── Totals ─────────────────────────────────────────────────────────── --}}
@include('documents.verify.partials.totals')

{{-- ── Vehicle specifications — one card per motorcycle unit ──────── --}}
@foreach($document->items as $item)
    @if($item->motorcycleUnit)
        <div class="mt-6">
            @include('documents.verify.partials.vehicle-specs', [
                'unit'  => $item->motorcycleUnit,
                'model' => $item->motorcycleUnit->motorcycleModel,
            ])
        </div>
    @endif
@endforeach

@endsection
