{{--
    QR-code footer variant — used by ownership / verification documents.

    Variables expected in scope:
      $company  — Company model
      $qrSvg    — base64-encoded SVG QR string
--}}
<div class="pdf-footer">
    <table>
        <tr>
            <td style="width: 78%; vertical-align: top;">
                @if($company->footer ?? $company->invoice_footer ?? null)
                    {{ $company->footer ?: $company->invoice_footer }}
                @endif
                @if($company->bank_name || $company->rib)
                    <br>{{ __('messages.bank_details') }}: {{ $company->bank_name }} {{ $company->rib }}
                @endif
            </td>
            <td style="width: 22%; text-align: right; vertical-align: top;">
                @isset($qrSvg)
                <img style="width: 55px; height: 55px;"
                     src="data:image/svg+xml;base64,{{ $qrSvg }}"
                     alt="{{ __('messages.qr_verification') }}">
                <div style="font-size: 7.5px; text-align: center; color: #6b7280; margin-top: 2px;">
                    {{ __('messages.verify_document') }}
                </div>
                @endisset
            </td>
        </tr>
    </table>
</div>
