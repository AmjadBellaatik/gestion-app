@extends('layouts.admin')

@section('content')

    <h1>Dashboard</h1>

    <p>
        Current Company ID:
        {{ session('company_id') }}
    </p>

@endsection