@extends('documents.verify.layouts.master')

@section('page-title', __('messages.repair_invoice') . ' — ' . $document->document_number)

@section('content')
@php
    $unit  = $v->primaryUnit();
    $model = $unit?->motorcycleModel;
@endphp

{{-- ── Parties: Client + Motorcycle ──────────────────────────────────── --}}
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

{{-- ── Repair ticket reference ───────────────────────────────────────── --}}
@if($v->repairTicketNumber())
    <div class="mt-4 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <span class="font-semibold">{{ __('messages.repair_ticket') ?? 'Réf. réparation' }}:</span>
        <span>{{ $v->repairTicketNumber() }}</span>
    </div>
@endif

{{-- ── Vehicle in for repair ──────────────────────────────────────────── --}}
@if($unit && $model)
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 shadow-sm">
        <div class="bg-slate-800 px-4 py-3">
            <h3 class="text-sm font-semibold text-white">
                {{ __('messages.motorcycle') }} — {{ $model->marque }} {{ $model->modele }}
            </h3>
        </div>
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-slate-100 bg-white">
                <tr>
                    <th class="w-1/3 bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.chassis_number') }}</th>
                    <td class="px-4 py-2.5 font-bold text-slate-900">{{ $unit->chassis_number }}</td>
                </tr>
                @if($model->type)
                <tr>
                    <th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.type') }}</th>
                    <td class="px-4 py-2.5 font-medium text-slate-900">{{ $model->type }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
@endif

{{-- ── Labour / Parts items ────────────────────────────────────────────── --}}
<div class="mt-6">
    @include('documents.verify.partials.items-table', [
        'items'        => $document->items,
        'showDiscount' => false,
        'showTaxRate'  => true,
    ])
</div>

{{-- ── Totals ─────────────────────────────────────────────────────────── --}}
@include('documents.verify.partials.totals')

{{-- ── Repair notes ────────────────────────────────────────────────────── --}}
@if($document->notes)
    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ __('messages.notes') }}
        </div>
        <p class="text-sm text-slate-700">{{ $document->notes }}</p>
    </div>
@endif

@endsection
