<!DOCTYPE html>
<html
    lang="{{ $document->language }}"
    dir="{{ $document->language == 'ar' ? 'rtl' : 'ltr' }}"
>

<head>

    <meta charset="utf-8">

    <style>

        body {

            font-family: DejaVu Sans;
            font-size: 12px;

        }

        table {

            width: 100%;
            border-collapse: collapse;

        }

        table, th, td {

            border: 1px solid #000;

        }

        th, td {

            padding: 6px;

        }

        .no-border {

            border: none !important;

        }

        .footer {

            margin-top: 120px;
            text-align: center;

        }

    </style>

</head>

<body>

    <table width="100%" class="no-border">

        <tr>

            <td width="50%" class="no-border">

                @if($company->logo)

                    <img
                        src="{{ public_path('storage/' . $company->logo) }}"
                        height="80"
                        style="max-width: 80px; object-fit: contain;"
                    >

                @endif

            </td>

            <td width="50%" class="no-border">

                <strong>
                    {{ $company->name }}
                </strong>

                <br>

                ICE:
                {{ $company->ice }}

                <br>

                RC:
                {{ $company->rc }}

                <br>

                IF:
                {{ $company->if }}

            </td>

        </tr>

    </table>

    <br>

    <table width="100%" class="no-border">

        <tr>

            <td width="70%" class="no-border">

                <h2>

                    {{ $document->document_number }}

                </h2>

            </td>

            <td width="30%" align="right" class="no-border"></td>

        </tr>

    </table>

    <table>

        <thead>

            <tr>

                <th>Description</th>

                <th>Qty</th>

                <th>Price</th>

                <th>Total</th>

            </tr>

        </thead>

        <tbody>

            @foreach(
                $document->items
                as $item
            )

                <tr>

                    <td>
                        {{ $item->description }}
                    </td>

                    <td>
                        {{ $item->quantity }}
                    </td>

                    <td>
                        {{ number_format(
                            $item->unit_price,
                            2
                        ) }}
                    </td>

                    <td>
                        {{ number_format(
                            $item->total,
                            2
                        ) }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <br>

    <table width="40%" align="right">

        <tr>

            <th>Subtotal</th>

            <td>
                {{ number_format(
                    $document->subtotal,
                    2
                ) }}
            </td>

        </tr>

        <tr>

            <th>Tax</th>

            <td>
                {{ number_format(
                    $document->tax,
                    2
                ) }}
            </td>

        </tr>

        <tr>

            <th>Total</th>

            <td>
                {{ number_format(
                    $document->total,
                    2
                ) }}
            </td>

        </tr>

    </table>

    <br><br><br><br><br>

    <div class="footer">

        @if($company->footer)

            {!! \App\Helpers\HtmlSanitizer::clean($company->footer) !!}

        @endif

    </div>

    <div
        style="
            margin-top: 40px;
            text-align: right;
        "
    >

        <div
            style="
                font-size: 12px;
                margin-bottom: 10px;
            "
        >

            {{ __('messages.verify_document') }}

        </div>

        {!! QrCode::size(100)->generate(
            $document->verification_url
        ) !!}

    </div>

</body>
</html>
