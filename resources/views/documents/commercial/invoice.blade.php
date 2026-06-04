@extends('documents.layouts.commercial')

@section('content')

<div class="document-title">

    {{ $document->documentType?->name }}

</div>

<table width="100%" style="margin-bottom:20px;">

    <tr>

        <td>

            <strong>
                {{ __('messages.client') }}
            </strong>

            <br>

            {{ $document->client?->name }}

        </td>

        <td align="right">

            <strong>
                {{ __('messages.date') }}
            </strong>

            :
            {{ $document->document_date }}

            <br>

            <strong>
                N°
            </strong>

            :
            {{ $document->document_number }}

        </td>

    </tr>

</table>

<table class="table">

    <thead>

        <tr>

            <th>
                #
            </th>

            <th>
                {{ __('messages.description') }}
            </th>

            <th>
                {{ __('messages.quantity') }}
            </th>

            <th>
                {{ __('messages.unit_price') }}
            </th>

            <th>
                {{ __('messages.total') }}
            </th>

        </tr>

    </thead>

    <tbody>

        @foreach($document->items as $item)

            <tr>

                <td>
                    {{ $loop->iteration }}
                </td>

                <td>
                    {{ $item->description }}
                </td>

                <td>
                    {{ $item->quantity }}
                </td>

                <td>
                    {{ number_format($item->unit_price, 2) }}
                </td>

                <td>
                    {{ number_format($item->total, 2) }}
                </td>

            </tr>

        @endforeach

    </tbody>

</table>

<table class="table totals">

    <tr>

        <td>
            HT
        </td>

        <td>
            {{ number_format($document->subtotal, 2) }}
        </td>

    </tr>

    <tr>

        <td>
            TVA
        </td>

        <td>
            {{ number_format($document->tax_amount, 2) }}
        </td>

    </tr>

    <tr>

        <td>
            TTC
        </td>

        <td>
            {{ number_format($document->total_amount, 2) }}
        </td>

    </tr>

</table>

<div style="margin-top:40px;">

    <strong>

        {{ __('messages.amount_in_words') }}

    </strong>

    :

    {{ \App\Services\Amounts\AmountInWordsService::convert(

        $document->total_amount,

        $document->language

    ) }}

</div>

@if($document->brand?->invoice_terms)

    <div style="margin-top:30px;">

        {!! \App\Helpers\HtmlSanitizer::clean($document->brand->invoice_terms) !!}

    </div>

@endif

@endsection