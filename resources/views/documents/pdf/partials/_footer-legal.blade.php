{{-- Legal-info footer — appears in normal document flow after the last content item. --}}
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
