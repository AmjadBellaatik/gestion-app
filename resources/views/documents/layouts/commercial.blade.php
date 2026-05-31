<!DOCTYPE html>
<html lang="{{ $document->language ?? 'fr' }}">

<head>

    <meta charset="UTF-8">

    <title>
        {{ $document->document_number }}
    </title>

    <style>

        body {

            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;

            margin: 0;
            padding: 0;

        }

        .header {

            width: 100%;
            margin-bottom: 20px;

        }

        .logo {

            max-height: 90px;

        }

        .company-info {

            font-size: 11px;
            line-height: 1.6;

        }

        .document-title {

            text-align: center;

            font-size: 22px;
            font-weight: bold;

            margin-top: 20px;
            margin-bottom: 20px;

        }

        .table {

            width: 100%;
            border-collapse: collapse;

        }

        .table th,
        .table td {

            border: 1px solid #000;
            padding: 8px;

        }

        .totals {

            margin-top: 20px;
            width: 300px;
            margin-left: auto;

        }

        .footer {

            position: fixed;

            bottom: 0;

            width: 100%;

            text-align: center;

            font-size: 10px;

        }

    </style>

</head>

<body>

    @if($template->header_enabled)

        <table class="header">

            <tr>

                <td width="30%">

                    @if($document->brand?->logo)

                        <img
                            src="{{ public_path('storage/' . $document->brand->logo) }}"
                            class="logo"
                        >

                    @endif

                </td>

                <td width="70%" class="company-info">

                    <strong>
                        {{ $document->company?->name }}
                    </strong>

                    <br>

                    {{ $document->company?->legal_address }}

                    <br>

                    ICE:
                    {{ $document->company?->ice }}

                    |
                    IF:
                    {{ $document->company?->if }}

                    |
                    RC:
                    {{ $document->company?->rc }}

                </td>

            </tr>

        </table>

    @endif

    @yield('content')

    @if($template->footer_enabled)

        <div class="footer">

            {!! $document->brand?->footer !!}

        </div>

    @endif

</body>

</html>