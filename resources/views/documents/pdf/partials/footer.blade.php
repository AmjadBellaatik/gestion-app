<div class="footer">
    <table class="grid">
        <tr>
            <td style="width: 78%;">
                {{ $company->footer ?: $company->invoice_footer }}
                <br>
                {{ __('messages.bank_details') }}: {{ $company->bank_name }} {{ $company->rib }}
            </td>
            <td style="width: 22%; text-align: right;">
                <img class="qr" src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="{{ __('messages.qr_verification') }}">
                <div>{{ __('messages.verify_document') }}</div>
            </td>
        </tr>
    </table>
</div>
