<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Historically this migration wrote the installer lock file
 * (storage/app/installed) for pre-existing deployments during
 * `php artisan migrate`.
 *
 * That was unsafe: `php artisan migrate` also runs inside the fresh-install
 * wizard (STEP 4), so a filesystem "installation complete" side effect could
 * fire before the company / super-admin / finalize steps had run and lock
 * the wizard out at /install/company.
 *
 * Lock-writing has been removed from the migration entirely. Legacy
 * detection now lives in guarded runtime code
 * (InstallationState::looksLikeCompletedLegacyInstall(), which additionally
 * requires a populated `users` table and a `Super Admin` role) and in the
 * explicit `php artisan app:mark-installed` command.
 *
 * The migration is kept as an inert no-op so migration history stays
 * consistent on machines that already recorded it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Intentionally does nothing. See the class docblock.
    }

    public function down(): void
    {
        // Intentionally does nothing.
    }
};
