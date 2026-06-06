{{--
    QR-footer for documents that use the shared partials (e.g. ownership-prsk).

    Safe-zone contract (enforced by partials/styles.blade.php):
      .footer { position: fixed; bottom: 0; height: 24mm; background: #fff; }
      @page { margin-bottom: 24mm; }
    These two values must remain equal.
--}}
<div class="footer">
    <table>
        <tr>
            <td style="width: 78%; vertical-align: top;">
                {{ $company->footer ?: $company->invoice_footer }}
                <br>
                {{ __('messages.bank_details') }}: {{ $company->bank_name }} {{ $company->rib }}
            </td>
            <td style="width: 22%; text-align: right; vertical-align: top;">
                <img style="width:55px;height:55px;" src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="{{ __('messages.qr_verification') }}">
                <div>{{ __('messages.verify_document') }}</div>
            </td>
        </tr>
    </table>
</div>
