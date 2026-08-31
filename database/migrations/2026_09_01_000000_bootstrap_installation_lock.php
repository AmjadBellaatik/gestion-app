<?php

use App\Services\Installer\InstallationState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backward-compatibility bridge for the first-run installer.
 *
 * An EXISTING deployment that pulls this code already has a populated
 * database. When `php artisan migrate` runs this migration there, it drops
 * the installation lock file immediately, so the installer stays invisible
 * and the live app is never interrupted.
 *
 * On a FRESH install the wizard runs migrations itself while the `users`
 * table is still empty — this migration then does nothing, and the wizard
 * writes the lock on its own at the finalize step.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only treat this as an existing install if there is real data.
        if (! Schema::hasTable('users')) {
            return;
        }

        try {
            $hasUsers = DB::table('users')->count() > 0;
        } catch (\Throwable) {
            return;
        }

        if (! $hasUsers) {
            return;
        }

        $state = app(InstallationState::class);

        if (! $state->isLocked()) {
            $state->markInstalled('upgrade migration: pre-existing installation');
        }
    }

    public function down(): void
    {
        // Never auto-delete the lock: rolling back a migration must not
        // re-expose the installer on a live server.
    }
};
