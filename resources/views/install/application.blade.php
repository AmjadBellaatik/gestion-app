@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.application.heading') }}</h2>
        <p>{{ __('install.application.intro') }}</p>
    </div>
    <form method="POST" action="{{ route('install.application.store') }}">
        @csrf
        <div class="card__body">
            <div class="field">
                <label for="name">{{ __('install.application.name') }}</label>
                <input type="text" name="name" id="name" required value="{{ old('name', $old['name']) }}">
            </div>
            <div class="field">
                <label for="url">{{ __('install.application.url') }} <span class="hint">· {{ __('install.application.url_hint') }}</span></label>
                <input type="url" name="url" id="url" required value="{{ old('url', $old['url']) }}" placeholder="https://erp.example.com">
            </div>
            <div class="field">
                <label for="locale">{{ __('install.application.locale') }}</label>
                <select name="locale" id="locale">
                    @foreach($locales as $code => $label)
                        <option value="{{ $code }}" @selected(old('locale', $old['locale']) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <p class="muted">{{ __('install.application.prod_note') }}</p>
        </div>
        <div class="card__foot">
            <a href="{{ route('install.database') }}" class="btn btn--ghost">← {{ __('install.back') }}</a>
            <button type="submit" class="btn btn--primary">{{ __('install.continue') }} →</button>
        </div>
    </form>
@endsection
