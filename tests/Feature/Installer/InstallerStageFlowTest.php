<?php

namespace Tests\Feature\Installer;

use App\Models\User;
use App\Services\Installer\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression cover for the production "GET /install/company -> 404" bug.
 *
 * All of these run with installer.trust_schema = true (the production
 * default) and a populated `migrations` table (RefreshDatabase), which is
 * exactly the state that used to make the installer think it was "installed"
 * the moment STEP 4 had run.
 */
class InstallerStageFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;
    private InstallationState $state;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = storage_path('framework/testing/installer-stage-'.uniqid());
        File::ensureDirectoryExists($this->tmp);

        config([
            'installer.enable_in_tests' => true,
            'installer.trust_schema' => true,        // production default
            'installer.lock_path' => $this->tmp.'/installed',
            'installer.state_path' => $this->tmp.'/state',
        ]);

        $this->state = app(InstallationState::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    /* SCENARIO B — migrations exist, install incomplete ---------------- */

    #[Test]
    public function company_step_is_reachable_after_migrations_have_run(): void
    {
        // Wizard reached STEP 4: schema initialised, no company/admin yet.
        $this->state->complete('requirements_ok');
        $this->state->complete('database_configured');
        $this->state->complete('schema_initialized');

        // migrations table is populated (RefreshDatabase) and APP_KEY is set,
        // but users is empty and there is no lock -> NOT installed.
        $this->assertFalse($this->state->isInstalled());
        $this->assertTrue($this->state->isInProgress());

        $this->get('/install/company')
            ->assertOk()
            ->assertSee(__('install.company.heading'));

        $this->assertFileDoesNotExist($this->tmp.'/installed');
    }

    #[Test]
    public function a_premature_lock_does_not_block_a_wizard_that_is_still_in_progress(): void
    {
        $this->state->complete('requirements_ok');
        $this->state->complete('database_configured');
        $this->state->complete('schema_initialized');

        // Simulate the old bug: something wrote the lock too early.
        $this->state->markInstalled('premature');

        $this->assertTrue($this->state->isInProgress());
        $this->assertFalse($this->state->isInstalled());

        $this->get('/install/company')->assertOk();
        $this->get('/install/admin')->assertRedirect(route('install.company')); // stage gate, not 404
    }

    /* SCENARIO C — lost / corrupted transient state ------------------- */

    #[Test]
    public function lost_transient_state_redirects_to_a_recoverable_step_not_404(): void
    {
        // No state file at all (session/file lost). App not installed.
        $this->assertFalse($this->state->isInstalled());
        $this->assertFalse($this->state->isInProgress());

        $this->get('/install/company')->assertRedirect(route('install.requirements'));
        $this->get('/install/admin')->assertRedirect(route('install.requirements'));
        $this->get('/install')->assertRedirect(route('install.requirements'));
    }

    #[Test]
    public function corrupted_state_file_is_treated_as_fresh_not_as_a_hard_404(): void
    {
        File::ensureDirectoryExists($this->tmp.'/state');
        File::put($this->tmp.'/state/state.json', '{ this is : not json ');

        $this->assertFalse($this->state->isInProgress());
        $this->get('/install/company')->assertRedirect(route('install.requirements'));
    }

    /* SCENARIO D — legacy existing installation ---------------------- */

    #[Test]
    public function a_completed_legacy_database_without_a_lock_self_heals_and_hides_the_installer(): void
    {
        // Real completion evidence: a user + the Super Admin role.
        Role::findOrCreate('Super Admin', 'web');
        User::forceCreate([
            'name' => 'Legacy Admin',
            'email' => 'legacy@example.com',
            'password' => Hash::make('x'),
        ]);

        $this->assertFalse($this->state->isInProgress());
        $this->assertTrue($this->state->isInstalled());       // self-heal
        $this->assertFileExists($this->tmp.'/installed');     // lock written

        $this->get('/install/company')->assertNotFound();
        $this->get('/install')->assertNotFound();
    }

    #[Test]
    public function migrations_alone_are_not_enough_to_be_considered_installed(): void
    {
        // migrations table populated by RefreshDatabase, but no users and no
        // Super Admin role -> a fresh wizard DB, NOT a completed install.
        $this->assertFalse($this->state->isInProgress());
        $this->assertFalse($this->state->isInstalled());
        $this->assertFileDoesNotExist($this->tmp.'/installed');
    }
}
