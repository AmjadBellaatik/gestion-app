<!DOCTYPE html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
>

<head>

    <meta charset="utf-8">

    <title>

        {{ $title ?? config('app.name') }}

    </title>

    @php
        $layoutCompany = session('company');
        $primaryColor = $layoutCompany?->primary_color ?: '#222';
        $secondaryColor = $layoutCompany?->secondary_color ?: '#222';
        $accentColor = $layoutCompany?->accent_color ?: '#f2f2f2';
    @endphp

    <style>

        /*
        |--------------------------------------------------------------------------
        | GLOBAL
        |--------------------------------------------------------------------------
        */

        * {

            box-sizing: border-box;

        }

        body {

            font-family: DejaVu Sans;

            font-size: 12px;

            color: {{ $secondaryColor }};

            margin: 0;
            padding: 0;

        }

        .container {

            width: 100%;

            padding: 20px;

        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {

            width: 100%;

            margin-bottom: 20px;

        }

        .header-table {

            width: 100%;

            border-collapse: collapse;

        }

        .header-table td {

            vertical-align: top;

        }

        .logo {

            width: 80px;
            height: 80px;
            object-fit: contain;

        }

        .company-name {

            font-size: 22px;

            font-weight: bold;

            margin-bottom: 5px;

        }

        .company-info {

            font-size: 11px;

            line-height: 18px;

        }

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT TITLE
        |--------------------------------------------------------------------------
        */

        .document-title {

            text-align: center;

            font-size: 24px;

            font-weight: bold;

            margin-top: 20px;
            margin-bottom: 20px;

        }

        /*
        |--------------------------------------------------------------------------
        | SECTIONS
        |--------------------------------------------------------------------------
        */

        .section {

            margin-bottom: 20px;

        }

        .section-title {

            font-size: 15px;

            font-weight: bold;

            margin-bottom: 10px;

            border-bottom: 1px solid #ccc;

            padding-bottom: 5px;

        }

        /*
        |--------------------------------------------------------------------------
        | TABLES
        |--------------------------------------------------------------------------
        */

        table {

            width: 100%;

            border-collapse: collapse;

        }

        th {

            background: {{ $accentColor }};

            font-weight: bold;

        }

        table,
        th,
        td {

            border: 1px solid #ccc;

        }

        th,
        td {

            padding: 8px;

            text-align:
                {{ app()->getLocale() === 'ar'
                    ? 'right'
                    : 'left'
                }};

        }

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        .totals {

            width: 300px;

            margin-top: 20px;

            margin-left:
                {{ app()->getLocale() === 'ar'
                    ? '0'
                    : 'auto'
                }};

            margin-right:
                {{ app()->getLocale() === 'ar'
                    ? 'auto'
                    : '0'
                }};

        }

        .totals td {

            padding: 10px;

        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer {

            margin-top: 40px;

            font-size: 10px;

            text-align: center;

            border-top: 1px solid {{ $primaryColor }};

            padding-top: 10px;

            color: {{ $secondaryColor }};

        }

        /*
        |--------------------------------------------------------------------------
        | HELPERS
        |--------------------------------------------------------------------------
        */

        .text-right {

            text-align: right;

        }

        .text-center {

            text-align: center;

        }

        .bold {

            font-weight: bold;

        }

        .mt-20 {

            margin-top: 20px;

        }

        .mb-20 {

            margin-bottom: 20px;

        }

        /*
        |--------------------------------------------------------------------------
        | PAGE BREAK
        |--------------------------------------------------------------------------
        */

        .page-break {

            page-break-after: always;

        }

    </style>

</head>

<body>

<div class="container">

    @php

        $company =
            session('company');

    @endphp

    {{-- HEADER --}}

    <div class="header">

        <table class="header-table">

            <tr>

                {{-- LOGO --}}

                <td width="25%">

                    @if(
                        $company?->logo
                    )

                        <img

                            src="{{ public_path(
                                'storage/' .
                                $company->logo
                            ) }}"

                            class="logo"
                        >

                    @endif

                </td>

                {{-- COMPANY INFO --}}

                <td width="50%">

                    <div class="company-name">

                        {{ $company?->name }}

                    </div>

                    <div class="company-info">

                        @if($company?->address)

                            {{ $company->address }}

                            <br>

                        @endif

                        @if($company?->phone)

                            {{ __('messages.phone') }}:

                            {{ $company->phone }}

                            <br>

                        @endif

                        @if($company?->email)

                            {{ __('messages.email') }}:

                            {{ $company->email }}

                            <br>

                        @endif

                        @if($company?->website)

                            {{ __('messages.website') }}:

                            {{ $company->website }}

                            <br>

                        @endif

                    </div>

                </td>

                {{-- LEGAL INFO --}}

                <td width="25%">

                    <div class="company-info">

                        @if($company?->ice)

                            ICE:

                            {{ $company->ice }}

                            <br>

                        @endif

                        @if($company?->rc)

                            RC:

                            {{ $company->rc }}

                            <br>

                        @endif

                        @if($company?->if)

                            {{ __('messages.tax_number') }}:

                            {{ $company->if }}

                            <br>

                        @endif

                    </div>

                </td>

            </tr>

        </table>

    </div>

    {{-- DOCUMENT TITLE --}}

    @isset($documentTitle)

        <div class="document-title">

            {{ $documentTitle }}

        </div>

    @endisset

    {{-- MAIN CONTENT --}}

    @yield('content')

    {{-- FOOTER --}}

    <div class="footer">

        @if(
            $company?->invoice_footer
        )

            {{ $company->invoice_footer }}

        @else

            {{ config('app.name') }}

        @endif

    </div>

</div>

</body>
</html>
