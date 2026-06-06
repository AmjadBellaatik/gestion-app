{{--
    Legal-info footer — text-only variant.

    Rendered by layouts/master.blade.php inside @section('footer-partial').
    Uses .pdf-footer class defined in the master's IMMUTABLE SAFE-ZONE block.

    Safe-zone contract (enforced by master.blade.php, Block 3):
        .pdf-footer { position:fixed; bottom:0; height:22mm; background:#fff; }
        @page { margin-bottom: 22mm; }
    Both values are 22mm.  Content area ends at the same physical point
    where the footer begins.  No overlap is possible.

    CONTENT MEASUREMENT (9px font, 1.4 line-height, 2 rows):
        Each row: 9px × 1.4 = 12.6px ≈ 3.3mm
        Two rows: 6.6mm
        Padding-top: 4mm
        Border-top: ~0.3mm
        Total content: ~10.9mm
        Footer height: 22mm
        Safety buffer: 22 - 10.9 = 11.1mm  ← generous margin for wrapped lines
--}}
<div class="pdf-footer">
    <table>
        <tr>
            <td style="width: 50%;">
                {{ $company->address ?: $company->legal_address }}
                @if($company->city) &mdash; {{ strtoupper($company->city) }}@endif
            </td>
            <td style="width: 50%; text-align: right;">
                @if($company->phone) Tél : {{ $company->phone }} @endif
                @if($company->email) | {{ $company->email }} @endif
            </td>
        </tr>
        <tr>
            <td>
                @if($company->ice) ICE : {{ $company->ice }} @endif
                @if($company->rc) | RC : {{ $company->rc }} @endif
                @if($company->if) | IF : {{ $company->if }} @endif
            </td>
            <td style="text-align: right;">
                @if($company->patente) Patente : {{ $company->patente }} @endif
                @if($company->cnss) | CNSS : {{ $company->cnss }} @endif
            </td>
        </tr>
    </table>
</div>
