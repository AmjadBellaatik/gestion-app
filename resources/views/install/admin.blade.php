@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.admin.heading') }}</h2>
        <p>{{ __('install.admin.intro') }}</p>
    </div>
    <form method="POST" action="{{ route('install.admin.store') }}" autocomplete="off">
        @csrf
        <div class="card__body">
            <div class="field">
                <label for="name">{{ __('install.admin.name') }}</label>
                <input type="text" name="name" id="name" required value="{{ old('name') }}">
            </div>
            <div class="field">
                <label for="email">{{ __('install.admin.email') }}</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}">
            </div>
            <div class="row">
                <div class="field">
                    <label for="password">{{ __('install.admin.password') }}</label>
                    <input type="password" name="password" id="password" required autocomplete="new-password">
                    <span class="hint">{{ __('install.admin.password_policy') }}</span>
                </div>
                <div class="field">
                    <label for="password_confirmation">{{ __('install.admin.password_confirm') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
        </div>
        <div class="card__foot">
            <span class="muted">{{ __('install.admin.foot_note') }}</span>
            <button type="submit" class="btn btn--primary">{{ __('install.continue') }} →</button>
        </div>
    </form>
@endsection
