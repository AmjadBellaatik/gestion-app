@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.initialize.heading') }}</h2>
        <p>{{ __('install.initialize.intro') }}</p>
    </div>
    <form method="POST" action="{{ route('install.initialize.run') }}" onsubmit="var b=this.querySelector('button[type=submit]');b.disabled=true;b.textContent=@json(__('install.initialize.running'));">
        @csrf
        <div class="card__body">
            <ul class="notes">
                <li>{{ __('install.initialize.task_migrate') }}</li>
                <li>{{ __('install.initialize.task_permissions') }}</li>
                <li>{{ __('install.initialize.task_verify') }}</li>
            </ul>
            <p class="muted" style="margin-top:14px">{{ __('install.initialize.time_note') }}</p>
        </div>
        <div class="card__foot">
            <a href="{{ route('install.application') }}" class="btn btn--ghost">← {{ __('install.back') }}</a>
            <button type="submit" class="btn btn--primary">{{ __('install.initialize.run') }} →</button>
        </div>
    </form>
@endsection
