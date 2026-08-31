@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.finalize.heading') }}</h2>
        <p>{{ __('install.finalize.intro') }}</p>
    </div>
    <form method="POST" action="{{ route('install.finalize.run') }}" onsubmit="var b=this.querySelector('button[type=submit]');b.disabled=true;b.textContent=@json(__('install.finalize.running'));">
        @csrf
        <div class="card__body">
            <div class="checks">
                <div><span>{{ __('install.finalize.company') }}</span><span class="tag tag--ok">{{ $summary['company']['name'] ?? '—' }}</span></div>
                <div><span>{{ __('install.finalize.admin') }}</span><span class="tag tag--ok">{{ $summary['admin']['email'] ?? '—' }}</span></div>
                <div><span>{{ __('install.finalize.app_url') }}</span><span class="tag tag--opt">{{ $summary['application']['url'] ?? config('app.url') }}</span></div>
                <div><span>{{ __('install.finalize.migrations') }}</span><span class="tag tag--ok">{{ $summary['migrations'] ?? '—' }}</span></div>
            </div>
            <div class="sec-title">{{ __('install.finalize.will_do') }}</div>
            <ul class="notes">
                <li>{{ __('install.finalize.task_key') }}</li>
                <li>{{ __('install.finalize.task_prod') }}</li>
                <li>{{ __('install.finalize.task_cache') }}</li>
                <li>{{ __('install.finalize.task_lock') }}</li>
            </ul>
        </div>
        <div class="card__foot">
            <span class="muted">{{ __('install.finalize.foot_note') }}</span>
            <button type="submit" class="btn btn--primary">{{ __('install.finalize.run') }} →</button>
        </div>
    </form>
@endsection
