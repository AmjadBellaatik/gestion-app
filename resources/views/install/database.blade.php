@extends('install.layout')

@section('content')
    <div class="card__head">
        <h2>{{ __('install.database.heading') }}</h2>
        <p>{{ __('install.database.intro') }}</p>
    </div>
    <form method="POST" action="{{ route('install.database.store') }}">
        @csrf
        <div class="card__body">
            <div class="row">
                <div class="field">
                    <label for="driver">{{ __('install.database.driver') }}</label>
                    <select name="driver" id="driver">
                        <option value="mysql"   @selected(old('driver', $old['driver'] ?? 'mysql') === 'mysql')>MySQL / MariaDB</option>
                        <option value="pgsql"   @selected(old('driver', $old['driver'] ?? '') === 'pgsql')>PostgreSQL</option>
                        <option value="sqlite"  @selected(old('driver', $old['driver'] ?? '') === 'sqlite')>SQLite</option>
                    </select>
                </div>
                <div class="field">
                    <label for="database">{{ __('install.database.name') }}</label>
                    <input type="text" name="database" id="database" required
                           value="{{ old('database', $old['database'] ?? 'gestion_app') }}" autocomplete="off">
                </div>
            </div>

            <div class="row-3" data-server-only>
                <div class="field">
                    <label for="host">{{ __('install.database.host') }}</label>
                    <input type="text" name="host" id="host"
                           value="{{ old('host', $old['host'] ?? '127.0.0.1') }}" autocomplete="off">
                </div>
                <div class="field">
                    <label for="port">{{ __('install.database.port') }}</label>
                    <input type="number" name="port" id="port"
                           value="{{ old('port', $old['port'] ?? 3306) }}" autocomplete="off">
                </div>
                <div class="field">
                    <label for="username">{{ __('install.database.username') }}</label>
                    <input type="text" name="username" id="username"
                           value="{{ old('username', $old['username'] ?? 'root') }}" autocomplete="off">
                </div>
            </div>

            <div class="field" data-server-only>
                <label for="password">{{ __('install.database.password') }} <span class="hint">· {{ __('install.database.password_hint') }}</span></label>
                <input type="password" name="password" id="password" value="" autocomplete="new-password">
            </div>

            <button type="button" id="test-btn" class="btn btn--ghost">{{ __('install.database.test') }}</button>
            <span id="test-result" class="muted" style="margin-inline-start:10px"></span>
        </div>
        <div class="card__foot">
            <a href="{{ route('install.requirements') }}" class="btn btn--ghost">← {{ __('install.back') }}</a>
            <button type="submit" id="save-btn" class="btn btn--primary">{{ __('install.database.save') }} →</button>
        </div>
    </form>

    <script>
        (function () {
            var driver = document.getElementById('driver');
            var testBtn = document.getElementById('test-btn');
            var out = document.getElementById('test-result');
            var form = testBtn.closest('form');

            function toggleServerOnly() {
                var hide = driver.value === 'sqlite';
                document.querySelectorAll('[data-server-only]').forEach(function (el) {
                    el.style.display = hide ? 'none' : '';
                });
            }
            driver.addEventListener('change', toggleServerOnly);
            toggleServerOnly();

            testBtn.addEventListener('click', function () {
                out.textContent = @json(__('install.database.testing'));
                out.style.color = '#64748b';
                testBtn.disabled = true;
                var body = new FormData(form);
                fetch(@json(route('install.database.test')), {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': body.get('_token'), 'Accept': 'application/json'},
                    body: body
                }).then(function (r) { return r.json().then(function (j) { return {ok: r.ok, j: j}; }); })
                  .then(function (res) {
                      out.textContent = res.j.message || '';
                      out.style.color = res.j.ok ? '#059669' : '#dc2626';
                  })
                  .catch(function () {
                      out.textContent = @json(__('install.errors.generic'));
                      out.style.color = '#dc2626';
                  })
                  .finally(function () { testBtn.disabled = false; });
            });
        })();
    </script>
@endsection
