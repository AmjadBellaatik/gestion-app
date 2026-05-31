<!doctype html>
<html lang="{{ $document->language }}" dir="{{ $document->language === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    @include('documents.pdf.partials.styles')
</head>
<body class="{{ $document->language === 'ar' ? 'rtl' : '' }}">
    <div class="official-title">{{ __('messages.ownership_document') }}</div>
    @php
        $unit = $motorcycleUnit;
        $model = $unit?->motorcycleModel;
    @endphp
    <table class="official-table">
        <tr><th colspan="4">{{ __('messages.owner_identity') }}</th></tr>
        <tr>
            <td>{{ __('messages.full_name') }}</td>
            <td>{{ $client?->display_name }}</td>
            <td>{{ __('messages.cin') }}</td>
            <td>{{ $client?->cin }}</td>
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
    <table class="signatures">
        <tr>
            <td>{{ __('messages.owner_signature') }}</td>
            <td>{{ __('messages.company') }}</td>
        </tr>
    </table>
    @include('documents.pdf.partials.footer')
</body>
</html>
