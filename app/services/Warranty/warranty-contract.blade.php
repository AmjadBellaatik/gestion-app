@extends('documents.layouts.legal')

@section('content')

<div
    class="absolute"
    style="top:80px;left:80px;"
>

    <strong>

        CONTRAT DE GARANTIE

    </strong>

</div>

<div
    class="absolute"
    style="top:150px;left:80px;"
>

    Client:
    {{ $contract->client?->name }}

</div>

<div
    class="absolute"
    style="top:180px;left:80px;"
>

    Moto:
    {{ $contract->motorcycle?->brand }}
    {{ $contract->motorcycle?->model }}

</div>

<div
    class="absolute"
    style="top:210px;left:80px;"
>

    VIN:
    {{ $contract->motorcycle?->vin_number }}

</div>

<div
    class="absolute"
    style="top:240px;left:80px;"
>

    Livraison:
    {{ $contract->delivery_date }}

</div>

<div
    class="absolute"
    style="top:270px;left:80px;"
>

    Expiration:
    {{ $contract->expiration_date }}

</div>

<div
    class="absolute"
    style="top:300px;left:80px;width:600px;"
>

    {!! $contract->warranty_terms !!}

</div>

<div
    class="absolute"
    style="top:420px;left:80px;width:600px;"
>

    <strong>
        Exclusions:
    </strong>

    <br>

    {!! $contract->warranty_exclusions !!}

</div>

<div
    class="absolute"
    style="bottom:120px;left:80px;"
>

    Signature Client

</div>

<div
    class="absolute"
    style="bottom:120px;right:120px;"
>

    Signature Vendeur

</div>

@endsection