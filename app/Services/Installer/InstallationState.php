<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Owns the two pieces of installer persistence:
 *
 *  1. The transient state-machine file  storage/app/install/state.json
 *     — tracks how far the wizard has progressed so a half-finished install
 *       can be retried without duplicating data.
 *
 *  2. The permanent lock file            storage/app/installed
 *     — its existence == "installed". Written only when the final stage
 *       completes (or by app:mark-installed / the upgrade migration for
 *       pre-existing deployments). Never removed automatically.
 */
class InstallationState
{
    /** Ordered pipeline of installation stages. */
    public const STAGES = [
        'requirements_ok',
        'database_configured',
        'schema_initialized',
        'company_created',
        'admin_created',
        'finalized',
    ];

    public function lockPath(): string
    {
        return (string) config('installer.lock_path', storage_path('app/installed'));
    }

    public function statePath(): string
    {
        return rtrim((string) config('installer.state_path', storage_path('app/install')), '/\\')
            .DIRECTORY_SEPARATOR.'state.json';
    }

    /* ----------------------------------------------------------------- */
    /* Lock file                                                        */
    /* ----------------------------------------------------------------- */

    public function isLocked(): bool
    {
        return is_file($this->lockPath());
    }

    /**
     * Authoritative "is this application installed?" check.
     *
     * Lock file wins. Otherwise, when trust_schema is enabled, an app that
     * already has a populated `migrations` table and a real APP_KEY is
     * considered installed and the lock is written so the check stays cheap.
     */
    public function isInstalled(): bool
    {
        if ($this->isLocked()) {
            return true;
        }

        if (! config('installer.trust_schema', true)) {
            return false;
        }

        if (blank(config('app.key'))) {
            return false;
        }

        try {
            if (! Schema::hasTable('migrations')) {
                return false;
            }

            if (DB::table('migrations')->count() <= 0) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        // Valid, migrated database without a lock → an existing deployment
        // that pulled this code. Heal it silently.
        $this->markInstalled('auto-heal: migrated database detected');

        return true;
    }

    public function markInstalled(string $reason = 'installer'): void
    {
        $payload = [
            'installed_at' => now()->toIso8601String(),
            'reason' => $reason,
            'app_version' => $this->appVersion(),
        ];

        File::ensureDirectoryExists(dirname($this->lockPath()));
        File::put(
            $this->lockPath(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    /* ----------------------------------------------------------------- */
    /* Transient state machine                                          */
    /* ----------------------------------------------------------------- */

    /** @return array{stage:?string,data:array,updated_at:?string} */
    public function read(): array
    {
        $default = ['stage' => null, 'data' => [], 'updated_at' => null];

        if (! is_file($this->statePath())) {
            return $default;
        }

        $decoded = json_decode((string) file_get_contents($this->statePath()), true);

        if (! is_array($decoded)) {
            return $default;
        }

        return array_merge($default, $decoded);
    }

    public function stage(): ?string
    {
        return $this->read()['stage'];
    }

    public function data(): array
    {
        return $this->read()['data'] ?? [];
    }

    /**
     * Record that a stage completed, merging any extra non-sensitive data.
     * Stages only ever move forward.
     */
    public function complete(string $stage, array $mergeData = []): void
    {
        if (! in_array($stage, self::STAGES, true)) {
            throw new \InvalidArgumentException("Unknown installation stage [{$stage}].");
        }

        $current = $this->read();

        $furthest = $this->maxStage($current['stage'], $stage);

        $payload = [
            'stage' => $furthest,
            'data' => array_replace_recursive($current['data'] ?? [], $mergeData),
            'updated_at' => now()->toIso8601String(),
        ];

        File::ensureDirectoryExists(dirname($this->statePath()));
        File::put(
            $this->statePath(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function mergeData(array $data): void
    {
        $current = $this->read();
        $current['data'] = array_replace_recursive($current['data'] ?? [], $data);
        $current['updated_at'] = now()->toIso8601String();

        File::ensureDirectoryExists(dirname($this->statePath()));
        File::put($this->statePath(), json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function isAtLeast(?string $stage): bool
    {
        if ($stage === null) {
            return true;
        }

        $current = $this->stage();

        if ($current === null) {
            return false;
        }

        return array_search($current, self::STAGES, true)
            >= array_search($stage, self::STAGES, true);
    }

    public function nextStage(): string
    {
        $current = $this->stage();

        if ($current === null) {
            return self::STAGES[0];
        }

        $idx = (int) array_search($current, self::STAGES, true);

        return self::STAGES[min($idx + 1, count(self::STAGES) - 1)];
    }

    /** Wipe the transient state (not the lock). Used on an explicit "start over". */
    public function resetTransient(): void
    {
        if (is_file($this->statePath())) {
            @unlink($this->statePath());
        }
    }

    private function maxStage(?string $a, ?string $b): string
    {
        $ia = $a === null ? -1 : (int) array_search($a, self::STAGES, true);
        $ib = $b === null ? -1 : (int) array_search($b, self::STAGES, true);

        return self::STAGES[max($ia, $ib, 0)];
    }

    private function appVersion(): string
    {
        try {
            $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

            return (string) ($composer['version'] ?? app()->version());
        } catch (Throwable) {
            return app()->version();
        }
    }
}
