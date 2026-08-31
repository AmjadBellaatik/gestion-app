<?php

namespace App\Console\Commands;

use App\Services\Installer\InstallationState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallStatusCommand extends Command
{
    protected $signature = 'app:install-status {--json : Output machine-readable JSON}';

    protected $description = 'Show installer / environment health: installed state, DB connectivity, migrations and the lock file.';

    public function handle(InstallationState $state): int
    {
        $report = [
            'installed' => $state->isLocked(),
            'lock_file' => $state->lockPath(),
            'lock_exists' => $state->isLocked(),
            'transient_stage' => $state->stage(),
            'app_key_set' => filled(config('app.key')),
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'database' => $this->database(),
            'migrations' => $this->migrations(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('<fg=cyan>Installed (lock file)</>', $report['installed'] ? '<fg=green>yes</>' : '<fg=yellow>no</>');
        $this->components->twoColumnDetail('Lock file path', $report['lock_file']);
        $this->components->twoColumnDetail('Transient stage', $report['transient_stage'] ?? '—');
        $this->components->twoColumnDetail('APP_KEY set', $report['app_key_set'] ? 'yes' : '<fg=red>no</>');
        $this->components->twoColumnDetail('APP_ENV', (string) $report['app_env']);
        $this->components->twoColumnDetail('APP_DEBUG', $report['app_debug'] ? '<fg=yellow>true</>' : 'false');
        $this->components->twoColumnDetail('Database connection', $report['database']['ok'] ? '<fg=green>ok</> ('.$report['database']['name'].')' : '<fg=red>unreachable</>');
        $this->components->twoColumnDetail('Migrations table', $report['migrations']['table'] ? 'present ('.$report['migrations']['count'].' rows)' : '<fg=yellow>absent</>');
        $this->components->twoColumnDetail('Pending migrations', (string) $report['migrations']['pending']);

        if (! $report['installed'] && $report['database']['ok'] && $report['migrations']['count'] > 0) {
            $this->newLine();
            $this->components->warn('A migrated database exists but there is no lock file. Run `php artisan app:mark-installed` to hide the installer on this deployment.');
        }

        return self::SUCCESS;
    }

    private function database(): array
    {
        try {
            DB::connection()->getPdo();

            return ['ok' => true, 'name' => DB::connection()->getDatabaseName()];
        } catch (Throwable) {
            return ['ok' => false, 'name' => null];
        }
    }

    private function migrations(): array
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return ['table' => false, 'count' => 0, 'pending' => 0];
            }

            $ran = DB::table('migrations')->count();

            $files = collect(glob(database_path('migrations/*.php')))
                ->map(fn ($p) => str_replace('.php', '', basename($p)))
                ->count();

            return [
                'table' => true,
                'count' => $ran,
                'pending' => max($files - $ran, 0),
            ];
        } catch (Throwable) {
            return ['table' => false, 'count' => 0, 'pending' => 0];
        }
    }
}
