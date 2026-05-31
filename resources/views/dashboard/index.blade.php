@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="row g-4">

        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>
                        {{ __('messages.revenue') }}
                    </h6>

                    <h3>
                        {{ number_format($stats['revenue'], 2) }}
                    </h3>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>
                        {{ __('messages.expenses') }}
                    </h6>

                    <h3>
                        {{ number_format($stats['expenses'], 2) }}
                    </h3>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>
                        {{ __('messages.pending_invoices') }}
                    </h6>

                    <h3>
                        {{ $stats['pending_invoices'] }}
                    </h3>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>
                        {{ __('messages.stock_alerts') }}
                    </h6>

                    <h3>
                        {{ $stats['stock_alerts'] }}
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <div class="row g-4 mt-3">

        <div class="col-md-6">

            <div class="card shadow-sm">

                <div class="card-header">

                    {{ __('messages.latest_invoices') }}

                </div>

                <div class="card-body">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>
                                    {{ __('messages.total') }}
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach(
                                $stats['latest_invoices']
                                as $invoice
                            )

                                <tr>

                                    <td>
                                        {{ $invoice->id }}
                                    </td>

                                    <td>
                                        {{ $invoice->total_ttc ?? 0 }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="card shadow-sm">

                <div class="card-header">

                    {{ __('messages.latest_payments') }}

                </div>

                <div class="card-body">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>
                                    {{ __('messages.amount') }}
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach(
                                $stats['latest_payments']
                                as $payment
                            )

                                <tr>

                                    <td>
                                        {{ $payment->id }}
                                    </td>

                                    <td>
                                        {{ $payment->amount }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <div class="row g-4 mt-3">

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>
                        {{ __('messages.repairs') }}
                    </h6>

                    <h3>
                        {{ $stats['repairs'] }}
                    </h3>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>
                        {{ __('messages.warranty_repairs') }}
                    </h6>

                    <h3>
                        {{ $stats['warranty_repairs'] }}
                    </h3>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>
                        {{ __('messages.reseller_credits') }}
                    </h6>

                    <h3>
                        {{ number_format(
                            $stats['reseller_credits'],
                            2
                        ) }}
                    </h3>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection