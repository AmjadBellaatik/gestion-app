@extends('layouts.admin')

@section('content')

<div class="container-fluid">

    <h1 class="mb-4">Dashboard</h1>

    <div class="row">

        <!-- Revenue -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Revenue</h5>
                    <h2>{{ number_format($revenue, 2) }} MAD</h2>
                </div>
            </div>
        </div>

        <!-- Pending Invoices -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Pending Invoices</h5>
                    <h2>{{ $pendingInvoices }}</h2>
                </div>
            </div>
        </div>

        <!-- Stock Alerts -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Stock Alerts</h5>
                    <h2>{{ $stockAlerts->count() }}</h2>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <!-- Latest Invoices -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Latest Invoices
                </div>

                <div class="card-body">

                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($latestInvoices as $invoice)
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td>{{ $invoice->client_name }}</td>
                                <td>{{ $invoice->total }} MAD</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <!-- Latest Payments -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Latest Payments
                </div>

                <div class="card-body">

                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Method</th>
                                <th>Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($latestPayments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->method }}</td>
                                <td>{{ $payment->amount }} MAD</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <!-- Chart -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Revenue Chart
                </div>

                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Top Products
                </div>

                <div class="card-body">

                    <ul class="list-group">

                        @foreach($topProducts as $product)

                        <li class="list-group-item d-flex justify-content-between">
                            {{ $product->name }}
                            <span>{{ $product->sold_count }}</span>
                        </li>

                        @endforeach

                    </ul>

                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@section('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ctx = document.getElementById('revenueChart');

new Chart(ctx, {
    type: 'line',

    data: {
        labels: {!! json_encode($monthlyRevenue->keys()) !!},

        datasets: [{
            label: 'Revenue',
            data: {!! json_encode($monthlyRevenue->values()) !!},
            borderWidth: 2
        }]
    }
});

</script>

@endsection