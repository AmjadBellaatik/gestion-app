@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h2 class="mb-4">

        {{ __('messages.reports') }}

    </h2>

    <form method="GET">

        <div class="row g-3 mb-4">

            <div class="col-md-3">

                <input
                    type="date"
                    name="date_from"
                    class="form-control"
                    value="{{ request('date_from') }}"
                >

            </div>

            <div class="col-md-3">

                <input
                    type="date"
                    name="date_to"
                    class="form-control"
                    value="{{ request('date_to') }}"
                >

            </div>

            <div class="col-md-3">

                <select
                    name="company_id"
                    class="form-select"
                >

                    <option value="">
                        {{ __('messages.company') }}
                    </option>

                    @foreach($companies as $company)

                        <option
                            value="{{ $company->id }}"
                            @selected(
                                request('company_id')
                                == $company->id
                            )
                        >

                            {{ $company->name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="col-md-3">

                <button
                    class="btn btn-primary w-100"
                >

                    {{ __('messages.filter') }}

                </button>

            </div>

        </div>

    </form>

    <div class="row g-4">

        <div class="col-md-4">

            <div class="card">

                <div class="card-body">

                    <h5>
                        {{ __('messages.profits') }}
                    </h5>

                    <p>

                        {{ __('messages.income') }}:
                        {{ number_format(
                            $profits['income'],
                            2
                        ) }}

                    </p>

                    <p>

                        {{ __('messages.expenses') }}:
                        {{ number_format(
                            $profits['expenses'],
                            2
                        ) }}

                    </p>

                    <p>

                        {{ __('messages.profit') }}:
                        {{ number_format(
                            $profits['profit'],
                            2
                        ) }}

                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-8">

            <div class="card">

                <div class="card-header">

                    {{ __('messages.sales') }}

                </div>

                <div class="card-body">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>
                                    {{ __('messages.total') }}
                                </th>

                                <th>
                                    {{ __('messages.date') }}
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($sales as $sale)

                                <tr>

                                    <td>
                                        {{ $sale->id }}
                                    </td>

                                    <td>
                                        {{ $sale->total_ttc ?? 0 }}
                                    </td>

                                    <td>
                                        {{ $sale->sale_date ?? $sale->created_at }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection