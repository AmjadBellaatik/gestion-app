@php($locale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('install.title') }} · {{ config('app.name') }}</title>
    <style>
        :root{
            --bg:#eef2f7; --card:#ffffff; --ink:#1e293b; --muted:#64748b;
            --line:#e2e8f0; --brand:#4f46e5; --brand-d:#4338ca;
            --ok:#059669; --ok-bg:#ecfdf5; --bad:#dc2626; --bad-bg:#fef2f2;
            --warn:#b45309; --warn-bg:#fffbeb; --radius:14px;
        }
        *{box-sizing:border-box}
        html,body{margin:0;padding:0}
        body{
            font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif;
            background:var(--bg); color:var(--ink); line-height:1.55;
            -webkit-font-smoothing:antialiased;
        }
        .wrap{max-width:860px;margin:0 auto;padding:40px 20px 64px}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:28px}
        .brand .logo{
            width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--brand),#7c3aed);
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px;
        }
        .brand h1{font-size:18px;margin:0;font-weight:650}
        .brand p{margin:0;color:var(--muted);font-size:13px}
        .card{
            background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
            box-shadow:0 1px 3px rgba(15,23,42,.06),0 12px 32px -12px rgba(15,23,42,.12);
            overflow:hidden;
        }
        .card__head{padding:24px 28px 18px;border-bottom:1px solid var(--line)}
        .card__head h2{margin:0 0 4px;font-size:20px}
        .card__head p{margin:0;color:var(--muted);font-size:14px}
        .card__body{padding:24px 28px}
        .card__foot{
            padding:18px 28px;border-top:1px solid var(--line);background:#fafbfc;
            display:flex;justify-content:space-between;align-items:center;gap:12px;
        }
        .steps{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:22px;counter-reset:s}
        .steps li{
            list-style:none;font-size:12px;color:var(--muted);padding:6px 10px;border-radius:999px;
            border:1px solid var(--line);background:#fff;display:flex;align-items:center;gap:6px;
        }
        .steps li .n{
            width:18px;height:18px;border-radius:50%;background:var(--line);color:#fff;
            display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;
        }
        .steps li.done{color:var(--ok);border-color:#a7f3d0;background:var(--ok-bg)}
        .steps li.done .n{background:var(--ok)}
        .steps li.active{color:var(--brand-d);border-color:#c7d2fe;background:#eef2ff}
        .steps li.active .n{background:var(--brand)}
        label{display:block;font-weight:600;font-size:13.5px;margin:0 0 6px}
        .hint{color:var(--muted);font-weight:400;font-size:12.5px}
        input[type=text],input[type=url],input[type=email],input[type=password],input[type=number],select{
            width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;
            background:#fff;color:var(--ink);transition:border-color .15s,box-shadow .15s;
        }
        input:focus,select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(79,70,229,.15)}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
        .field{margin-bottom:16px}
        @media (max-width:620px){.row,.row-3{grid-template-columns:1fr}}
        .btn{
            appearance:none;border:1px solid transparent;border-radius:10px;padding:10px 18px;
            font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;
            align-items:center;gap:8px;
        }
        .btn--primary{background:var(--brand);color:#fff}
        .btn--primary:hover{background:var(--brand-d)}
        .btn--ghost{background:#fff;color:var(--ink);border-color:#cbd5e1}
        .btn--ghost:hover{background:#f1f5f9}
        .btn[disabled]{opacity:.55;cursor:not-allowed}
        .alert{border-radius:10px;padding:12px 14px;font-size:13.5px;margin-bottom:18px;border:1px solid}
        .alert--bad{background:var(--bad-bg);border-color:#fecaca;color:#991b1b}
        .alert--ok{background:var(--ok-bg);border-color:#a7f3d0;color:#065f46}
        .alert--warn{background:var(--warn-bg);border-color:#fde68a;color:#92400e}
        .checks{border:1px solid var(--line);border-radius:12px;overflow:hidden}
        .checks div{display:flex;justify-content:space-between;align-items:center;padding:9px 14px;font-size:13.5px;border-top:1px solid var(--line)}
        .checks div:first-child{border-top:none}
        .tag{font-size:12px;font-weight:700;padding:2px 9px;border-radius:999px}
        .tag--ok{background:var(--ok-bg);color:var(--ok)}
        .tag--bad{background:var(--bad-bg);color:var(--bad)}
        .tag--opt{background:#f1f5f9;color:var(--muted)}
        .muted{color:var(--muted);font-size:13px}
        .sec-title{font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin:22px 0 10px;font-weight:700}
        .sec-title:first-child{margin-top:0}
        ul.notes{margin:8px 0 0;padding-inline-start:18px;font-size:13px;color:var(--muted)}
        code{background:#f1f5f9;padding:1px 6px;border-radius:6px;font-size:12.5px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <div class="logo">{{ mb_substr(config('app.name','A'),0,1) }}</div>
        <div>
            <h1>{{ config('app.name') }}</h1>
            <p>{{ __('install.subtitle') }}</p>
        </div>
    </div>

    @isset($step)
        <ol class="steps">
            @foreach($step['labels'] as $i => $label)
                <li class="{{ $i+1 < $step['number'] ? 'done' : ($i+1 === $step['number'] ? 'active' : '') }}">
                    <span class="n">{{ $i+1 < $step['number'] ? '✓' : $i+1 }}</span>
                    {{ __('install.steps.'.$label) }}
                </li>
            @endforeach
        </ol>
    @endisset

    <div class="card">
        @if(session('installer_error'))
            <div style="padding:16px 28px 0"><div class="alert alert--bad">{{ session('installer_error') }}</div></div>
        @endif
        @if($errors->any())
            <div style="padding:16px 28px 0">
                <div class="alert alert--bad">
                    <strong>{{ __('install.fix_errors') }}</strong>
                    <ul style="margin:6px 0 0;padding-inline-start:18px">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </div>

    <p class="muted" style="text-align:center;margin-top:22px">
        {{ __('install.footer_note') }}
    </p>
</div>
</body>
</html>
