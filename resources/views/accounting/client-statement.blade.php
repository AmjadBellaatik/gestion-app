<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.account_statement') }} — {{ $client->display_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color:#111827; margin:0; background:#f3f4f6; }
        .wrap { max-width: 1000px; margin: 24px auto; background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.08); overflow:hidden; }
        .head { display:flex; justify-content:space-between; align-items:flex-start; padding:24px; border-bottom:2px solid #111827; }
        .company { font-size:18px; font-weight:800; text-transform:uppercase; }
        .muted { color:#6b7280; font-size:13px; }
        .title { text-align:center; font-size:20px; font-weight:800; text-transform:uppercase; padding:16px; letter-spacing:.5px; }
        .meta { padding:0 24px 16px; display:flex; gap:32px; flex-wrap:wrap; }
        .meta b { display:block; font-size:11px; text-transform:uppercase; color:#6b7280; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#1f2937; color:#fff; text-align:left; padding:10px 14px; font-size:12px; }
        th.num, td.num { text-align:right; white-space:nowrap; }
        tbody td { padding:9px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; }
        tbody tr:nth-child(even) { background:#f9fafb; }
        .debit { color:#b45309; font-weight:600; }
        .credit { color:#047857; font-weight:600; }
        .summary { margin:18px 24px 28px auto; width:380px; }
        .summary td { padding:8px 14px; border-bottom:1px solid #e5e7eb; font-size:14px; }
        .summary .grand td { font-weight:800; font-size:16px; background:#f3f4f6; border-radius:6px; }
        .pos { color:#b91c1c; } .zero { color:#047857; }
        .toolbar { padding:16px 24px; background:#f9fafb; display:flex; gap:12px; justify-content:flex-end; }
        .btn { padding:9px 16px; border-radius:8px; border:0; font-weight:600; font-size:13px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-print { background:#111827; color:#fff; }
        .btn-pdf { background:#2563eb; color:#fff; }
        .btn-csv { background:#059669; color:#fff; }
        @media print { .toolbar { display:none; } body { background:#fff; } .wrap { box-shadow:none; margin:0; max-width:100%; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div>
            <div class="company">{{ $company?->name }}</div>
            <div class="muted">{{ $company?->address }} @if($company?->city)— {{ strtoupper($company->city) }}@endif</div>
            @if($company?->ice)<div class="muted">ICE : {{ $company->ice }} @if($company?->rc)| RC : {{ $company->rc }}@endif</div>@endif
        </div>
        <div class="muted" style="text-align:right;">
            {{ __('messages.date') }}<br><strong>{{ now()->format('d/m/Y') }}</strong>
        </div>
    </div>

    <div class="title">{{ __('messages.account_statement') }}</div>

    <div class="meta">
        <div><b>{{ __('messages.client') }}</b>{{ $client->display_name }}</div>
        @if($client->phone)<div><b>{{ __('messages.phone') }}</b>{{ $client->phone }}</div>@endif
        @if($client->email)<div><b>{{ __('messages.email') }}</b>{{ $client->email }}</div>@endif
    </div>

    <table>
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
            <tr><td colspan="5" style="text-align:center;padding:24px;color:#6b7280;">—</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td>{{ __('messages.total_sales') }}</td><td class="num">{{ number_format($totals['total_debit'], 2, ',', ' ') }} MAD</td></tr>
        <tr><td>{{ __('messages.total_payments') }}</td><td class="num">{{ number_format($totals['total_credit'], 2, ',', ' ') }} MAD</td></tr>
        @if($totals['credit_balance'] > 0)
        <tr><td>{{ __('messages.credit_balance') }}</td><td class="num">{{ number_format($totals['credit_balance'], 2, ',', ' ') }} MAD</td></tr>
        @endif
        <tr class="grand">
            <td>{{ __('messages.outstanding_balance') }}</td>
            <td class="num {{ $totals['outstanding'] > 0 ? 'pos' : 'zero' }}">{{ number_format($totals['outstanding'], 2, ',', ' ') }} MAD</td>
        </tr>
    </table>

    <div class="toolbar">
        <a href="{{ route('clients.statement.csv', $client) }}" class="btn btn-csv">{{ __('messages.export_excel') }}</a>
        <a href="{{ route('clients.statement.pdf', $client) }}" class="btn btn-pdf">{{ __('messages.export_pdf') }}</a>
        <button onclick="window.print()" class="btn btn-print">{{ __('messages.print') ?? 'Print' }}</button>
    </div>
</div>
</body>
</html>
