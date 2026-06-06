<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 12mm 20mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color:#111; font-size:11px; line-height:1.4; }
        .doc-footer {
            position: fixed; bottom: 0; left: 0; right: 0; height: 20mm;
            background:#fff; border-top:1px solid #777; padding:4mm 12mm 0;
            font-size:8.5px; color:#444;
        }
        .head { border-bottom:2px solid #111; padding-bottom:8px; margin-bottom:14px; }
        .company { font-size:15px; font-weight:700; text-transform:uppercase; }
        .title { text-align:center; font-size:18px; font-weight:700; text-decoration:underline; margin:10px 0; text-transform:uppercase; }
        .meta { margin-bottom:10px; }
        table.ledger { width:100%; border-collapse:collapse; margin-top:8px; }
        table.ledger th { background:#1f2937; color:#fff; padding:6px 7px; font-size:10px; text-align:left; }
        table.ledger th.num, table.ledger td.num { text-align:right; white-space:nowrap; }
        table.ledger td { border-bottom:1px solid #d1d5db; padding:5px 7px; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .summary { width:48%; margin-left:auto; margin-top:12px; border-collapse:collapse; page-break-inside:avoid; }
        .summary td { padding:5px 7px; border-bottom:1px solid #d1d5db; }
        .summary .grand td { font-weight:700; font-size:13px; background:#f3f4f6; }
        .credit { color:#047857; }
        .debit  { color:#b45309; }
    </style>
</head>
<body>
    <div class="head">
        <div class="company">{{ $company?->name }}</div>
        <div>{{ $company?->address }} @if($company?->city)— {{ strtoupper($company->city) }}@endif</div>
    </div>

    <div class="title">{{ __('messages.account_statement') }}</div>

    <div class="meta">
        <strong>{{ __('messages.client') }} :</strong> {{ $client->display_name }}
        @if($client->phone) &nbsp;|&nbsp; {{ __('messages.phone') }} : {{ $client->phone }} @endif
        <br>
        <strong>{{ __('messages.date') }} :</strong> {{ now()->format('d/m/Y') }}
    </div>

    <table class="ledger">
        <thead>
            <tr>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.document') }}</th>
                <th class="num">{{ __('messages.debit') }}</th>
                <th class="num">{{ __('messages.credit') }}</th>
                <th class="num">{{ __('messages.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $l)
            <tr>
                <td>{{ optional($l['date'])->format('d/m/Y') }}</td>
                <td>{{ $l['document'] }}</td>
                <td class="num debit">{{ $l['debit'] > 0 ? number_format($l['debit'], 2, ',', ' ').' MAD' : '' }}</td>
                <td class="num credit">{{ $l['credit'] > 0 ? number_format($l['credit'], 2, ',', ' ').' MAD' : '' }}</td>
                <td class="num">{{ number_format($l['balance'], 2, ',', ' ') }} MAD</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:14px;">-</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td>{{ __('messages.total_sales') }}</td><td class="num">{{ number_format($totals['total_debit'], 2, ',', ' ') }} MAD</td></tr>
        <tr><td>{{ __('messages.total_payments') }}</td><td class="num">{{ number_format($totals['total_credit'], 2, ',', ' ') }} MAD</td></tr>
        @if($totals['credit_balance'] > 0)
        <tr><td>{{ __('messages.credit_balance') }}</td><td class="num">{{ number_format($totals['credit_balance'], 2, ',', ' ') }} MAD</td></tr>
        @endif
        <tr class="grand"><td>{{ __('messages.outstanding_balance') }}</td><td class="num">{{ number_format($totals['outstanding'], 2, ',', ' ') }} MAD</td></tr>
    </table>

    <div class="doc-footer">
        {{ $company?->name }}
        @if($company?->ice) — ICE : {{ $company->ice }} @endif
        @if($company?->rc) — RC : {{ $company->rc }} @endif
    </div>
</body>
</html>
