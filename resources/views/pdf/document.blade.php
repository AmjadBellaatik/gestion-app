<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>
        {{ $document->document_number }}
    </title>

    <style>

        body {

            font-family: DejaVu Sans;

            font-size: 12px;

            color: #111;

        }

        .header {

            margin-bottom: 30px;

        }

        .title {

            font-size: 22px;

            font-weight: bold;

        }

        table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 20px;

        }

        table th,
        table td {

            border: 1px solid #ddd;

            padding: 10px;

        }

    </style>

</head>

<body>

    <div class="header">

        <div class="title">

            {{ $document->documentType?->name }}

        </div>

        <br>

        <strong>
            Number:
        </strong>

        {{ $document->document_number }}

        <br>

        <strong>
            Date:
        </strong>

        {{ $document->document_date }}

        <br>

        <strong>
            Client:
        </strong>

        {{ $document->client?->first_name }}

    </div>

    <table>

        <tr>

            <th>
                Subtotal
            </th>

            <th>
                Tax
            </th>

            <th>
                Total
            </th>

        </tr>

        <tr>

            <td>
                {{ $document->subtotal }}
            </td>

            <td>
                {{ $document->tax }}
            </td>

            <td>
                {{ $document->total }}
            </td>

        </tr>

    </table>

    <br><br>

    <strong>
        Notes:
    </strong>

    <br>

    {{ $document->notes }}

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