<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Owns the two pieces of installer persistence and the three-state model:
 *
 *  fresh / uninstalled  — no lock, no transient state, no completed schema.
 *  installing / in-progress — transient state file exists with a non-final
 *                             stage. The wizard is running RIGHT NOW; the
 *                             application is NOT installed no matter what the
 *                             database schema looks like.
 *  installed            — the lock file exists (written only by finalize() /
 *                         app:mark-installed / a guarded legacy self-heal).
 *
 *  1. Transient state file  storage/app/install/state.json
 *     — how far the running wizard has progressed. Deleted by finalize().
 *
 *  2. Lock file             storage/app/installed
 *     — the authoritative "installation complete" signal. Never written by a
 *       database migration; never written while a wizard is in progress.
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
     * TRUE while the first-run wizard is actively running: a transient state
     * file exists and its stage has not reached `finalized`.
     *
     * This is the guard that stops a half-finished fresh install from being
     * mistaken for a completed one — including the case where a stale lock
     * file was written prematurely by an earlier bug.
     */
    public function isInProgress(): bool
    {
        if (! is_file($this->statePath())) {
            return false;
        }

        $stage = $this->stage();

        return $stage !== null && $stage !== 'finalized';
    }

    /**
     * Authoritative "is this application installed?" check.
     *
     * Order matters:
     *   1. A wizard in progress is NEVER installed.
     *   2. The lock file is the completion signal.
     *   3. Legacy self-heal (trust_schema): only for a database that shows
     *      *real completion* evidence — migrations AND at least one user AND
     *      a Super Admin role. "migrations exist" alone is not enough, so a
     *      freshly-migrated wizard DB is not classified as installed.
     */
    public function isInstalled(): bool
    {
        if ($this->isInProgress()) {
            return false;
        }

        if ($this->isLocked()) {
            return true;
        }

        if (! config('installer.trust_schema', true)) {
            return false;
        }

        if (blank(config('app.key'))) {
            return false;
        }

        if (! $this->looksLikeCompletedLegacyInstall()) {
            return false;
        }

        // Pre-existing deployment that completed an install before this
        // installer shipped (no lock). Heal it once so the check stays cheap.
        $this->markInstalled('self-heal: pre-existing completed installation');

        return true;
    }

    /**
     * Strong "this database belongs to a finished installation" heuristic —
     * deliberately stricter than "migrations table has rows".
     */
    public function looksLikeCompletedLegacyInstall(): bool
    {
        try {
            if (! Schema::hasTable('migrations') || DB::table('migrations')->count() <= 0) {
                return false;
            }

            if (! Schema::hasTable('users') || DB::table('users')->count() <= 0) {
                return false;
            }

            if (! Schema::hasTable('roles')) {
                return false;
            }

            return DB::table('roles')->where('name', 'Super Admin')->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /** The one true "installation finished" writer. */
    public function markCompleted(string $reason = 'installer wizard completed'): void
    {
        $this->markInstalled($reason);
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
