<?php

namespace App\Console\Commands;

use App\Services\Installer\InstallationState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Locks the installer for an already-valid application without creating
 * any data. Intended for existing deployments upgrading to this release
 * that want to be explicit rather than rely on the upgrade migration.
 */
class MarkInstalledCommand extends Command
{
    protected $signature = 'app:mark-installed
                            {--force : Write the lock even if the schema check is inconclusive}';

    protected $description = 'Create the installation lock file for an existing, working application (creates no data).';

    public function handle(InstallationState $state): int
    {
        if ($state->isLocked()) {
            $this->components->info('Already locked: '.$state->lockPath());

            return self::SUCCESS;
        }

        $healthy = $this->schemaLooksInstalled();

        if (! $healthy && ! $this->option('force')) {
            $this->components->error(
                'This database does not look like an installed application '
                .'(missing core tables or empty). Re-run with --force only if you are certain.'
            );

            return self::FAILURE;
        }

        $state->markInstalled($healthy ? 'app:mark-installed' : 'app:mark-installed --force');

        $this->components->info('Installer locked → '.$state->lockPath());
        $this->components->info('The /install wizard now returns 404.');

        return self::SUCCESS;
    }

    private function schemaLooksInstalled(): bool
    {
        try {
            foreach (['users', 'companies', 'roles', 'permissions', 'migrations'] as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return DB::table('migrations')->count() > 0
                && DB::table('users')->count() > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
