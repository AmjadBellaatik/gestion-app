@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.requirements.heading') }}</h2>
        <p>{{ __('install.requirements.intro', ['app' => $report['app_name']]) }}</p>
    </div>
    <div class="card__body">
        @unless($report['ok'])
            <div class="alert alert--warn">{{ __('install.requirements.blocked') }}</div>
        @else
            <div class="alert alert--ok">{{ __('install.requirements.passed') }}</div>
        @endunless

        <div class="sec-title">PHP</div>
        <div class="checks">
            <div>
                <span>{{ __('install.requirements.php_version') }} <span class="hint">({{ $report['php']['required'] }})</span></span>
                <span class="tag {{ $report['php']['ok'] ? 'tag--ok' : 'tag--bad' }}">{{ $report['php']['current'] }}</span>
            </div>
        </div>

        <div class="sec-title">{{ __('install.requirements.extensions') }}</div>
        <div class="checks">
            @foreach($report['extensions'] as $ext)
                <div>
                    <span>{{ $ext['name'] }} @unless($ext['required'])<span class="hint">· {{ __('install.optional') }}</span>@endunless</span>
                    <span class="tag {{ $ext['loaded'] ? 'tag--ok' : ($ext['required'] ? 'tag--bad' : 'tag--opt') }}">
                        {{ $ext['loaded'] ? __('install.loaded') : __('install.missing') }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="sec-title">{{ __('install.requirements.writable') }}</div>
        <div class="checks">
            @foreach($report['writable'] as $w)
                <div>
                    <span><code>{{ $w['path'] }}</code></span>
                    <span class="tag {{ $w['ok'] ? 'tag--ok' : 'tag--bad' }}">{{ $w['ok'] ? __('install.writable') : __('install.not_writable') }}</span>
                </div>
            @endforeach
        </div>

        <div class="sec-title">{{ __('install.requirements.db_drivers') }}</div>
        <div class="checks">
            @foreach($report['database_drivers'] as $d)
                <div>
                    <span>{{ $d['name'] }}</span>
                    <span class="tag {{ $d['loaded'] ? 'tag--ok' : 'tag--opt' }}">{{ $d['loaded'] ? __('install.available') : __('install.missing') }}</span>
                </div>
            @endforeach
        </div>
    </div>
    <form method="POST" action="{{ route('install.requirements.store') }}" class="card__foot">
        @csrf
        <span class="muted">{{ __('install.step_of', ['n' => $step['number'], 'total' => $step['total']]) }}</span>
        <button type="submit" class="btn btn--primary" {{ $report['ok'] ? '' : 'disabled' }}>
            {{ __('install.continue') }} →
        </button>
    </form>
@endsection
