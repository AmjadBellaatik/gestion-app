@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.complete.heading') }}</h2>
        <p>{{ __('install.complete.intro', ['app' => $appName]) }}</p>
    </div>
    <div class="card__body">
        <div class="alert alert--ok">{{ __('install.complete.locked') }}</div>

        @if(session('installer_notes'))
            <div class="sec-title">{{ __('install.complete.done') }}</div>
            <ul class="notes">
                @foreach(session('installer_notes') as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="card__foot" style="justify-content:flex-end">
        <a href="{{ $loginUrl }}" class="btn btn--primary">{{ __('install.complete.go_login') }} →</a>
    </div>
@endsection
