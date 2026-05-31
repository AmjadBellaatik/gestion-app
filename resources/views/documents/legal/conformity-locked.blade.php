@extends('documents.layouts.legal')

@section('content')

<div
    class="absolute"
    style="top:80px;left:80px;"
>

    <strong>

        CERTIFICAT DE CONFORMITÉ

    </strong>

</div>

<div
    class="absolute"
    style="top:150px;left:80px;"
>

    Référence homologation:
    {{ $certificate->homologation_reference }}

</div>

<div
    class="absolute"
    style="top:180px;left:80px;"
>

    VIN:
    {{ $certificate->vin_number }}

</div>

<div
    class="absolute"
    style="top:210px;left:80px;"
>

    Moteur:
    {{ $certificate->engine_number }}

</div>

<div
    class="absolute rtl"
    style="top:300px;right:80px;width:320px;"
>

    {{ $certificate->official_wording }}

</div>

@if($certificate->is_validated)

    <div
        class="absolute"
        style="
            bottom:60px;
            left:80px;
            font-size:10px;
        "
    >

        HASH:
        {{ $certificate->hash }}

    </div>

@endif

@endsection