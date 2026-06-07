@extends('documents.verify.layouts.master')

@section('page-title', __('messages.warranty_contract') . ' — ' . $document->document_number)

@section('content')
@php
    $unit    = $v->primaryUnit();
    $model   = $unit?->motorcycleModel;
    $product = $document->items->first(fn ($i) => $i->product)?->product;

    $coveredItemName      = $unit
        ? trim(($model?->marque ? $model->marque . ' ' : '') . ($model?->modele ?: ''))
        : $product?->name;
    $coveredItemType      = $unit
        ? $model?->type
        : ($product?->type ? __('messages.' . $product->type) : null);
    $coveredItemReference = $unit
        ? $unit->chassis_number
        : ($product?->sku ?: $product?->barcode);
@endphp

{{-- ── Parties and warranty terms ──────────────────────────────────────── --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

    @include('documents.verify.partials.company-card', [
        'name'    => $document->company?->name,
        'address' => $document->company?->address,
        'logo'    => $document->company?->logo,
    ])

    @include('documents.verify.partials.client-card', [
        'name'    => $v->clientName(),
        'type'    => $v->clientType(),
        'cin'     => $v->clientIdentity(),
        'address' => $v->clientAddress(),
    ])

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            {{ __('messages.warranty_duration') }}
        </div>
        <div class="text-xl font-bold text-slate-900">
            {{ $v->warrantyDurationLabel() ?: '—' }}
        </div>
        @if($v->warrantyKilometers())
            <div class="mt-1 text-sm text-slate-600">
                {{ __('messages.warranty_distance') }}: {{ $v->warrantyKilometers() }} KM
            </div>
        @endif
    </div>

</div>

{{-- ── Covered item ──────────────────────────────────────────────────────── --}}
<div class="mt-6 overflow-hidden rounded-xl border border-slate-200 shadow-sm">
    <div class="bg-slate-800 px-4 py-3">
        <h3 class="text-sm font-semibold text-white">
            {{ $unit ? __('messages.motorcycle') : __('messages.product') }}
        </h3>
    </div>
    <table class="min-w-full text-sm">
        <tbody class="divide-y divide-slate-100 bg-white">
            @if($unit)
                <tr><th class="w-2/5 bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.marque') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->marque }}</td></tr>
                <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.model') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->modele }}</td></tr>
                <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.type') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->type }}</td></tr>
                <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.chassis_number') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $unit->chassis_number }}</td></tr>
            @else
                <tr><th class="w-2/5 bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.product') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $coveredItemName }}</td></tr>
                <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.type') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $coveredItemType }}</td></tr>
                <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.sku') }} / {{ __('messages.barcode') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $coveredItemReference }}</td></tr>
            @endif
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.address') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $v->clientAddress() }}</td></tr>
        </tbody>
    </table>
</div>

{{-- ── Warranty commitment statement ────────────────────────────────────── --}}
<div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900">
    @if($v->warrantyKilometers())
        {!! __('messages.warranty_commitment_km', [
            'company'  => '<strong>' . e($document->company?->name) . '</strong>',
            'duration' => '<strong>' . e($v->warrantyDurationLabel()) . '</strong>',
            'km'       => '<strong>' . e($v->warrantyKilometers()) . '</strong>',
        ]) !!}
    @else
        {!! __('messages.warranty_commitment', [
            'company'  => '<strong>' . e($document->company?->name) . '</strong>',
            'duration' => '<strong>' . e($v->warrantyDurationLabel()) . '</strong>',
        ]) !!}
    @endif
</div>

@endsection
