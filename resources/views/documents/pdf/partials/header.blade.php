<div class="header">
    @php
        $logoPath = $company?->logo ? public_path('storage/' . $company->logo) : null;
    @endphp
    <table class="grid">
        <tr>
            <td style="width: 55%;">
                <div class="company-heading">
                    @if($logoPath && file_exists($logoPath))
                        <div class="company-heading-logo">
                            <img src="{{ $logoPath }}" class="company-logo" alt="{{ __('messages.logo') }}">
                        </div>
                    @endif
                    <div class="company-heading-info">
                        <div class="brand">{{ $company->name }}</div>
                        <div>{{ $company->address ?: $company->legal_address }}</div>
                        <div>{{ __('messages.phone') }}: {{ $company->phone }} | {{ __('messages.email') }}: {{ $company->email }}</div>
                        <div class="small">
                            {{ __('messages.ice') }}: {{ $company->ice }} |
                            {{ __('messages.rc') }}: {{ $company->rc }} |
                            {{ __('messages.tax_number') }}: {{ $company->if }}
                        </div>
                    </div>
                </div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="title">{{ $title }}</div>
                <div>{{ __('messages.document_number') }}: <strong>{{ $document->document_number }}</strong></div>
                <div>{{ __('messages.document_date') }}: {{ $document->document_date?->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>
