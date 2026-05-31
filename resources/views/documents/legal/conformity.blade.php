@extends('documents.layouts.legal')

@section('content')

<div
    class="absolute"
    style="top:80px;left:80px;"
>

    <strong>

        CERTIFICAT DE CONFORMITE

    </strong>

</div>

<div
    class="absolute"
    style="top:150px;left:80px;"
>

    Marque:
    {{ $document->items->first()?->motorcycle?->brand }}

</div>

<div
    class="absolute"
    style="top:180px;left:80px;"
>

    Modèle:
    {{ $document->items->first()?->motorcycle?->model }}

</div>

<div
    class="absolute"
    style="top:210px;left:80px;"
>

    VIN:
    {{ $document->items->first()?->motorcycle?->vin_number }}

</div>

<div
    class="absolute rtl"
    style="top:300px;right:80px;width:300px;"
>

    شهادة المطابقة الرسمية الخاصة بالمركبة

</div>

@endsection