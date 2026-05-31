<!DOCTYPE html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
>

<head>

    <meta charset="UTF-8">

    <title>
        Test
    </title>

</head>

<body>

    <h1>

        {{ __('messages.dashboard') }}

    </h1>

    <p>

        Locale:
        {{ app()->getLocale() }}

    </p>

</body>

</html>