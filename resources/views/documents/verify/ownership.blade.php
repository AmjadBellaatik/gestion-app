@extends('documents.verify.layouts.master')

@section('page-title', __('messages.ownership_document') . ' — ' . $document->document_number)

@section('content')
@php
    $unit  = $v->primaryUnit();
    $model = $unit?->motorcycleModel;
    $client = $document->client;
@endphp

{{-- ── Parties ────────────────────────────────────────────────────────── --}}
<div class="grid gap-4 sm:grid-cols-2">

    @include('documents.verify.partials.company-card', [
        'name'    => $document->company?->name,
        'address' => $document->company?->address,
        'phone'   => $document->company?->phone,
        'logo'    => $document->company?->logo,
    ])

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            {{ __('messages.owner_identity') }}
        </div>
        <div class="font-bold text-slate-900">{{ $v->clientName() }}</div>
        @if($client?->identity_number)
            <div class="mt-1 text-sm text-slate-600">{{ $client->identity_label }}: {{ $client->identity_number }}</div>
        @endif
        @if($client?->address)
            <div class="mt-1 text-sm leading-snug text-slate-500">{{ $client->address }}</div>
        @endif
    </div>

</div>

{{-- ── Vehicle identification table ──────────────────────────────────── --}}
<div class="mt-6 overflow-hidden rounded-xl border border-slate-200 shadow-sm">
    <div class="bg-slate-800 px-4 py-3">
        <h3 class="text-sm font-semibold text-white">
            {{ __('messages.vehicle_identification') }}
        </h3>
    </div>
    <table class="min-w-full text-sm">
        <tbody class="divide-y divide-slate-100 bg-white">
            <tr>
                <th class="w-1/4 bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.marque') }}</th>
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->marque }}</td>
                <th class="w-1/4 bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.model') }}</th>
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->modele }}</td>
            </tr>
            <tr>
                <th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.type') }}</th>
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->type }}</td>
                <th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.chassis_number') }}</th>
                <td class="px-4 py-2.5 font-bold text-slate-900">{{ $unit?->chassis_number }}</td>
            </tr>
            <tr>
                <th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.conformity_category_label') }}</th>
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->categorie }}</td>
                <th class="bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('messages.homologation_number') }}</th>
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ $model?->titre_homologation }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ── Declaration notice ─────────────────────────────────────────────── --}}
<div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-700">
    <p>{{ __('messages.ownership_declaration_sentence') }}</p>
</div>

@endsection
