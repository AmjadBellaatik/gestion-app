@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h2 class="mb-4">

        {{ __('messages.settings') }}

    </h2>

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif

    <form
        method="POST"
        action="{{ route('settings.update') }}"
    >

        @csrf

        @method('PUT')

        @foreach($settings as $group => $items)

            <div class="card mb-4 shadow-sm">

                <div class="card-header">

                    <strong>

                        {{ ucfirst($group) }}

                    </strong>

                </div>

                <div class="card-body">

                    <div class="row">

                        @foreach($items as $setting)

                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label"
                                >

                                    {{ $setting->key }}

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="settings[{{ $setting->id }}]"
                                    value="{{ $setting->value }}"
                                >

                            </div>

                        @endforeach

                    </div>

                </div>

            </div>

        @endforeach

        <button
            type="submit"
            class="btn btn-primary"
        >

            {{ __('messages.save') }}

        </button>

    </form>

</div>

@endsection