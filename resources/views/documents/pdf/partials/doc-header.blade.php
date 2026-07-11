{{--
    Shared document header partial.

    Layout:
      LEFT  (75%) — brand block: logo on top, company name directly below.
                    Both are centered relative to each other.
                    The entire block is left-aligned on the page.
      RIGHT (25%) — QR verification badge (optional).

    DOMPDF note: a <div> inside a <td> may expand to full cell width, making
    text-align:center push the logo/name to the visual centre of the page.
    To guarantee left-alignment, the brand block is a width:auto nested table.
    DOMPDF always starts a width:auto table at the left edge of its container.

    Variables expected in scope:
      $company  — Company model (required)
      $qrSvg    — base64-encoded SVG QR string (optional)
--}}
{{-- Skipped in pre-printed mode: the header is already printed on the physical sheet. --}}
@unless($preprinted ?? false)
<table class="doc-header">
    <tr>
        {{-- LEFT: brand block (logo above company name, both centered together, block left-aligned) --}}
        <td style="width: 75%; vertical-align: middle; padding: 0;">
            <table style="border-collapse: collapse; width: auto; margin: 0;">
                <tr>
                    <td style="padding: 0; text-align: center; vertical-align: top;">
                        @if($company->logo)
                        <img style="display: block; margin: 0 auto; max-height: 56px; max-width: 140px;"
                             src="{{ public_path('storage/' . $company->logo) }}"
                             alt="{{ $company->name }}">
                        @endif
                        <div style="display: block; margin-top: 5px; font-size: 15px; font-weight: 700;
                                    text-transform: uppercase; text-align: center; letter-spacing: 0.03em;">
                            {{ $company->name }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>

        {{-- RIGHT: QR code --}}
        <td style="width: 25%; text-align: right; vertical-align: middle; padding: 0;">
            @isset($qrSvg)
            <img style="width: 56px; height: 56px; display: block; margin-left: auto;"
                 src="data:image/svg+xml;base64,{{ $qrSvg }}"
                 alt="QR">
            <div style="font-size: 7.5px; text-align: center; color: #6b7280; margin-top: 2px;">
                {{ __('messages.verify_document') }}
            </div>
            @endisset
        </td>
    </tr>
</table>
@endunless
