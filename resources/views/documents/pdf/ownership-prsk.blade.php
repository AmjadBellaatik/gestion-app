@extends('documents.pdf.layouts.master')

@section('footer-partial')
    @include('documents.pdf.partials._footer-qr')
@endsection

@push('styles')
<style>
    @php
        $primaryColor = $company?->primary_color ?: '#111827';
        $accentColor  = $company?->accent_color  ?: '#f3f4f6';
    @endphp

    /* Official document typography */
    .rtl { direction: rtl; text-align: right; }
    .official-title { text-align: center; font-size: 15px; font-weight: 700; text-transform: uppercase; margin: 12px 0; }

    /* Official data table */
    .official-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .official-table td, .official-table th { border: 1px solid {{ $primaryColor }}; padding: 5px; }
    .official-table th { background: {{ $accentColor }}; text-align: center; }

    /* Signature block for legal doc — two columns, equal width */
    .signatures td { height: 70px; vertical-align: bottom; }
</style>
@endpush

@section('content')
@php
    $unit  = $motorcycleUnit;
    $model = $unit?->motorcycleModel;
@endphp

    <div class="official-title">{{ __('messages.ownership_document') }}</div>

    <table class="official-table">
        <tr><th colspan="4">{{ __('messages.owner_identity') }}</th></tr>
        <tr>
            <td>{{ __('messages.full_name') }}</td>
            <td>{{ $client?->display_name }}</td>
            <td>{{ $client?->identity_label }}</td>
            <td>{{ $client?->identity_number }}</td>
        </tr>
        <tr>
            <td>{{ __('messages.address') }}</td>
            <td colspan="3">{{ $client?->address }}</td>
        </tr>
        <tr><th colspan="4">{{ __('messages.vehicle_identification') }}</th></tr>
        <tr>
            <td>{{ __('messages.marque') }}</td>
            <td>{{ $model?->marque }}</td>
            <td>{{ __('messages.model') }}</td>
            <td>{{ $model?->modele }}</td>
        </tr>
        <tr>
            <td>{{ __('messages.type') }}</td>
            <td>{{ $model?->type }}</td>
            <td>{{ __('messages.chassis_number') }}</td>
            <td><strong>{{ $unit?->chassis_number }}</strong></td>
        </tr>
        <tr>
            <td>{{ __('messages.category') }}</td>
            <td>{{ $model?->categorie }}</td>
            <td>{{ __('messages.homologation_number') }}</td>
            <td>{{ $model?->titre_homologation }}</td>
        </tr>
    </table>

    <p>{{ __('messages.ownership_declaration_sentence') }}</p>

    <div class="pdf-protect">
        <table class="signatures">
            <tr>
                <td>{{ __('messages.owner_signature') }}</td>
                <td>{{ __('messages.company') }}</td>
            </tr>
        </table>
    </div>
@endsection
