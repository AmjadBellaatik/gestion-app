@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.company.heading') }}</h2>
        <p>{{ __('install.company.intro') }}</p>
    </div>
    <form method="POST" action="{{ route('install.company.store') }}">
        @csrf
        <div class="card__body">
            <div class="sec-title">{{ __('install.company.identity') }}</div>
            <div class="row">
                <div class="field">
                    <label for="name">{{ __('install.company.name') }} *</label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $old['name'] ?? '') }}">
                </div>
                <div class="field">
                    <label for="legal_name">{{ __('install.company.legal_name') }}</label>
                    <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name') }}">
                </div>
            </div>

            <div class="sec-title">{{ __('install.company.contact') }}</div>
            <div class="row">
                <div class="field">
                    <label for="phone">{{ __('install.company.phone') }}</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}">
                </div>
                <div class="field">
                    <label for="email">{{ __('install.company.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}">
                </div>
            </div>
            <div class="row">
                <div class="field">
                    <label for="address">{{ __('install.company.address') }}</label>
                    <input type="text" name="address" id="address" value="{{ old('address') }}">
                </div>
                <div class="field">
                    <label for="city">{{ __('install.company.city') }}</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}">
                </div>
            </div>

            <div class="sec-title">{{ __('install.company.legal') }} <span class="hint">· {{ __('install.optional') }}</span></div>
            <div class="row-3">
                <div class="field"><label for="ice">ICE</label><input type="text" name="ice" id="ice" value="{{ old('ice') }}"></div>
                <div class="field"><label for="rc">RC</label><input type="text" name="rc" id="rc" value="{{ old('rc') }}"></div>
                <div class="field"><label for="if">IF</label><input type="text" name="if" id="if" value="{{ old('if') }}"></div>
            </div>
            <div class="row">
                <div class="field"><label for="patente">{{ __('install.company.patente') }}</label><input type="text" name="patente" id="patente" value="{{ old('patente') }}"></div>
            </div>
        </div>
        <div class="card__foot">
            <span class="muted">{{ __('install.company.foot_note') }}</span>
            <button type="submit" class="btn btn--primary">{{ __('install.continue') }} →</button>
        </div>
    </form>
@endsection
