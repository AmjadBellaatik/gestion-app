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

            position: relative;

            margin: 0;
            padding: 0;

            font-size: 12px;

        }

        .page {

            position: relative;

            width: 100%;
            height: 100%;

        }

        .absolute {

            position: absolute;

        }

        .rtl {

            direction: rtl;
            text-align: right;

        }

        .ltr {

            direction: ltr;
            text-align: left;

        }

    </style>

</head>

<body>

    <div class="page">

        @yield('content')

    </div>

</body>

</html>
