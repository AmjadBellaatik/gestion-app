@extends('documents.verify.layouts.master')

@section('page-title', __('messages.conformity_certificate_title') . ' — ' . $document->document_number)

@section('content')
@php
    $unit         = $v->primaryUnit();
    $model        = $unit?->motorcycleModel;
    $homologation = $model?->homologation;

    $brand                  = $model?->marque;
    $constructor            = $model?->usine_fabrication ?: $homologation?->manufacturer;
    $accreditationReference = data_get($document->metadata, 'accreditation_reference');
    $homologationNumber     = $model?->titre_homologation ?: $homologation?->homologation_number;
    $homologationDate       = $model?->date_homologation?->format('d/m/Y')
                              ?: $homologation?->homologation_date?->format('d/m/Y');
    $capacity               = $model?->cylindree ? $model->cylindree . ' CC' : null;
    $power                  = trim($capacity . ($model?->puissance_effective ? ' / ' . $model->puissance_effective : ''));
@endphp

{{-- ── Metadata cards ─────────────────────────────────────────────────── --}}
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
            {{ __('messages.conformity_constructor') }}
        </div>
        <div class="font-bold text-slate-900">{{ $constructor ?: '—' }}</div>
        @if($accreditationReference)
            <div class="mt-1 text-sm text-slate-600">Réf: {{ $accreditationReference }}</div>
        @endif
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            {{ __('messages.homologation_number') }}
        </div>
        <div class="font-bold text-slate-900">{{ $homologationNumber ?: '—' }}</div>
        @if($homologationDate)
            <div class="mt-1 text-sm text-slate-500">{{ $homologationDate }}</div>
        @endif
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            {{ __('messages.marque') }}
        </div>
        <div class="font-bold text-slate-900">{{ $brand ?: '—' }}</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            {{ __('messages.document_date') }}
        </div>
        <div class="font-bold text-slate-900">{{ $document->document_date?->format('d/m/Y') }}</div>
    </div>

</div>

{{-- ── Introductory text ───────────────────────────────────────────────── --}}
<div class="mt-6 space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-700">
    <p class="font-medium">
        {{ __('messages.conformity_intro', ['company' => $document->company?->name, 'brand' => $brand]) }}
    </p>
    <p>{{ __('messages.conformity_mandataire', ['company' => $document->company?->name]) }}</p>
    <p>{{ __('messages.conformity_vehicle_intro') }}</p>
</div>

{{-- ── Vehicle details table ──────────────────────────────────────────── --}}
<div class="mt-6 overflow-hidden rounded-xl border border-slate-200 shadow-sm">
    <div class="bg-slate-800 px-4 py-3">
        <h3 class="text-sm font-semibold text-white">
            {{ __('messages.conformity_certificate_title') }}
        </h3>
    </div>
    <table class="min-w-full text-sm">
        <tbody class="divide-y divide-slate-100 bg-white">
            <tr><th class="w-2/5 bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.marque') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $brand }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.genre') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->genre }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.model') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->modele }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.type') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->type }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.conformity_category_label') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->categorie }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.conformity_chassis_label') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $unit?->chassis_number }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.conformity_engine_power') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $power ?: '—' }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.fuel') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->carburant }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.conformity_cylinder_label') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->nombre_cylindres }}</td></tr>
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.conformity_client_name') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $v->clientName() }}</td></tr>
            @if($v->clientType() !== 'administration')
                <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.conformity_client_identity') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $v->clientIdentity() }}</td></tr>
            @endif
            <tr><th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.address') }}</th><td class="px-4 py-2.5 font-medium text-slate-900">{{ $v->clientAddress() }}</td></tr>
        </tbody>
    </table>
</div>

{{-- ── Homologation summary ────────────────────────────────────────────── --}}
<div class="mt-4 space-y-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
    <p>{{ __('messages.conformity_type_sentence') }}</p>
    <p>{{ __('messages.conformity_homologation_sentence', ['number' => $homologationNumber, 'date' => $homologationDate]) }}</p>
</div>

@endsection
