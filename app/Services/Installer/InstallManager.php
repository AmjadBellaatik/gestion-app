<?php

namespace App\Services\Installer;

use App\Models\Company;
use App\Models\User;
use App\Support\AppKey;
use App\Support\EnvFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Orchestrates the installation state machine. Each public method performs
 * exactly one stage, records progress via InstallationState, and is safe to
 * retry after a partial failure.
 */
class InstallManager
{
    public function __construct(
        private readonly InstallationState $state,
        private readonly DatabaseConfigurator $database,
        private readonly BootstrapSeeder $seeder,
    ) {
    }

    /* STEP 1 ----------------------------------------------------------- */

    public function markRequirementsPassed(): void
    {
        $this->state->complete('requirements_ok');
    }

    /* STEP 2 ----------------------------------------------------------- */

    /** @return array{ok:bool,message:string} */
    public function testDatabase(array $creds): array
    {
        return $this->database->test($creds);
    }

    public function configureDatabase(array $creds): void
    {
        $result = $this->database->test($creds);
        if (! $result['ok']) {
            throw new InstallerException($result['message']);
        }

        $this->database->ensureDatabaseExists($creds);
        $this->database->persist($creds);

        // Persist only non-sensitive fields into the transient state.
        $this->state->complete('database_configured', [
            'database' => [
                'driver' => $creds['driver'] ?? 'mysql',
                'host' => $creds['host'] ?? null,
                'port' => $creds['port'] ?? null,
                'database' => $creds['database'] ?? null,
                'username' => $creds['username'] ?? null,
            ],
        ]);
    }

    /* STEP 3 ----------------------------------------------------------- */

    public function configureApplication(array $data): void
    {
        $env = EnvFile::make();
        $env->write([
            'APP_NAME' => $data['name'],
            'APP_URL' => rtrim($data['url'], '/'),
            'APP_LOCALE' => $data['locale'] ?? config('app.locale', 'fr'),
        ]);

        config([
            'app.name' => $data['name'],
            'app.url' => rtrim($data['url'], '/'),
            'app.locale' => $data['locale'] ?? config('app.locale', 'fr'),
        ]);

        $this->state->mergeData([
            'application' => [
                'name' => $data['name'],
                'url' => rtrim($data['url'], '/'),
                'locale' => $data['locale'] ?? config('app.locale', 'fr'),
            ],
        ]);
    }

    /* STEP 4 ----------------------------------------------------------- */

    public function initializeSchema(): void
    {
        if (! $this->database->currentConnectionWorks()) {
            throw new InstallerException(__('install.errors.connection_refused'));
        }

        Artisan::call('config:clear');

        $exit = Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
        if ($exit !== 0) {
            throw new InstallerException(
                __('install.errors.migrations_failed').' '.trim(Artisan::output())
            );
        }

        $this->seeder->seedPlatform();

        $this->assertSchemaHealthy();

        $this->state->complete('schema_initialized', [
            'migrations' => DB::table('migrations')->count(),
        ]);
    }

    /* STEP 5 ----------------------------------------------------------- */

    public function createCompany(array $data): Company
    {
        $attributes = array_filter([
            'legal_name' => $data['legal_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? 'Morocco',
            'ice' => $data['ice'] ?? null,
            'rc' => $data['rc'] ?? null,
            'if' => $data['if'] ?? null,
            'patente' => $data['patente'] ?? null,
            'default_language' => $data['locale'] ?? config('app.locale', 'fr'),
            'currency' => $data['currency'] ?? 'MAD',
            'is_active' => true,
        ], static fn ($v) => $v !== null && $v !== '');

        // Idempotent on name: retrying STEP 5 reuses the same company.
        $company = Company::firstOrCreate(['name' => $data['name']], $attributes);

        // Fill any columns left blank on a previous partial attempt.
        $company->fill($attributes)->save();

        $this->seeder->seedCompanyReferenceData($company);

        $this->state->complete('company_created', [
            'company' => ['id' => $company->id, 'name' => $company->name],
        ]);

        return $company;
    }

    /* STEP 6 ----------------------------------------------------------- */

    public function createAdmin(array $data, ?Company $company = null): User
    {
        $company ??= $this->resolveFirstCompany();

        if (! $company) {
            throw new InstallerException(__('install.errors.no_company'));
        }

        $user = User::firstOrCreate(
            ['email' => mb_strtolower(trim($data['email']))],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'language' => $data['locale'] ?? config('app.locale', 'fr'),
            ]
        );

        // If the row pre-existed from a failed retry, refresh name/password.
        $user->forceFill([
            'name' => $data['name'],
            'password' => Hash::make($data['password']),
        ])->save();

        $superAdmin = \Spatie\Permission\Models\Role::where([
            'name' => BootstrapSeeder::SUPER_ADMIN_ROLE,
            'guard_name' => 'web',
        ])->first();

        if (! $user->hasRole(BootstrapSeeder::SUPER_ADMIN_ROLE)) {
            $user->assignRole(BootstrapSeeder::SUPER_ADMIN_ROLE);
        }

        // Multi-company link + per-company role, without duplicating rows.
        $user->companies()->syncWithoutDetaching([
            $company->id => ['role_id' => $superAdmin?->id],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->state->complete('admin_created', [
            'admin' => ['id' => $user->id, 'email' => $user->email],
        ]);

        return $user;
    }

    /* STEP 7 ----------------------------------------------------------- */

    /** @return array<int,string> human-readable notes about what happened */
    public function finalize(): array
    {
        $notes = [];
        $app = $this->state->data()['application'] ?? [];

        // 1) APP_KEY — generate ONLY when missing/invalid. Never clobber.
        if (AppKey::ensure()) {
            $notes[] = 'APP_KEY generated';
        } else {
            $notes[] = 'existing APP_KEY preserved';
        }

        // 2) Production hardening.
        EnvFile::make()->write([
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'APP_URL' => rtrim((string) ($app['url'] ?? config('app.url')), '/'),
        ]);
        config(['app.env' => 'production', 'app.debug' => false]);

        if (! config('installer.run_optimizations', true)) {
            $this->state->markCompleted();
            $this->state->complete('finalized');
            $this->state->resetTransient();
            $notes[] = 'optimizations skipped (installer.run_optimizations=false)';

            return $notes;
        }

        // 3) storage symlink (best effort — fails silently on hosts that
        //    disallow symlink()).
        try {
            if (! file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
                $notes[] = 'storage symlink created';
            }
        } catch (Throwable $e) {
            $notes[] = 'storage:link skipped ('.class_basename($e).')';
        }

        // 4) Cache rebuild — clear first, then rebuild the caches that are
        //    safe with this codebase. route:cache is intentionally skipped:
        //    routes/web.php defines closure routes which cannot be serialized.
        Artisan::call('optimize:clear');
        foreach (['config:cache', 'view:cache', 'event:cache'] as $command) {
            try {
                Artisan::call($command);
                $notes[] = $command;
            } catch (Throwable $e) {
                Artisan::call('optimize:clear');
                $notes[] = $command.' failed, caches cleared instead';
                break;
            }
        }

        // 5) Lock the installer permanently and drop the transient state.
        $this->state->markCompleted();
        $this->state->complete('finalized');
        $this->state->resetTransient();

        return $notes;
    }

    /* ----------------------------------------------------------------- */

    public function resolveFirstCompany(): ?Company
    {
        $id = $this->state->data()['company']['id'] ?? null;

        return $id
            ? Company::find($id) ?? Company::orderBy('id')->first()
            : Company::orderBy('id')->first();
    }

    private function assertSchemaHealthy(): void
    {
        $required = ['users', 'companies', 'company_user', 'roles', 'permissions', 'migrations', 'settings'];

        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                throw new InstallerException(__('install.errors.schema_unhealthy', ['table' => $table]));
            }
        }

        if (\Spatie\Permission\Models\Role::where('name', BootstrapSeeder::SUPER_ADMIN_ROLE)->doesntExist()) {
            throw new InstallerException(__('install.errors.schema_unhealthy', ['table' => 'roles(Super Admin)']));
        }
    }
}
