<?php

namespace Tests\Feature\Installer;

use App\Models\Company;
use App\Models\User;
use App\Services\Installer\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * SCENARIO E — an existing, already-working installation must never have
 * the wizard hijack it, even without the new lock file.
 */
class InstallerCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = storage_path('framework/testing/installer-'.uniqid());
        File::ensureDirectoryExists($this->tmp);

        config([
            'installer.lock_path' => $this->tmp.'/installed',
            'installer.state_path' => $this->tmp.'/state',
            'installer.enable_in_tests' => true,
            'installer.trust_schema' => true, // production default
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    #[Test]
    public function a_bare_migrated_database_is_NOT_treated_as_installed(): void
    {
        // RefreshDatabase populated `migrations` and APP_KEY is set, but there
        // are no users and no Super Admin role. This is what a fresh wizard DB
        // looks like right after STEP 4 — it must NOT count as installed and
        // must NOT get a lock written behind the wizard's back.
        $state = app(InstallationState::class);

        $this->assertFalse($state->isInProgress());
        $this->assertFalse($state->isInstalled());
        $this->assertFileDoesNotExist($this->tmp.'/installed');
    }

    #[Test]
    public function a_completed_legacy_database_self_heals_the_lock_and_hides_the_installer(): void
    {
        // Real completion evidence: at least one user + the Super Admin role.
        Role::findOrCreate('Super Admin', 'web');
        User::forceCreate([
            'name' => 'Legacy Admin',
            'email' => 'legacy@example.com',
            'password' => Hash::make('x'),
        ]);

        $this->assertFileDoesNotExist($this->tmp.'/installed');

        $this->assertTrue(app(InstallationState::class)->isInstalled());
        $this->assertFileExists($this->tmp.'/installed'); // healed

        $this->get('/install')->assertRedirect(config('installer.redirect_after'));
        $this->get('/')->assertRedirect('/admin');
    }

    #[Test]
    public function mark_installed_command_refuses_an_empty_database_without_force(): void
    {
        // No users yet → not obviously an installed app.
        $this->artisan('app:mark-installed')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->tmp.'/installed');
    }

    #[Test]
    public function mark_installed_command_locks_a_populated_database(): void
    {
        User::forceCreate([
            'name' => 'Legacy Admin',
            'email' => 'legacy@example.com',
            'password' => Hash::make('irrelevant-for-this-test'),
        ]);
        Company::forceCreate(['name' => 'Legacy Co']);

        $this->artisan('app:mark-installed')->assertExitCode(0);

        $this->assertFileExists($this->tmp.'/installed');
        $this->get('/install')->assertRedirect(config('installer.redirect_after'));
    }

    #[Test]
    public function install_status_command_reports_health(): void
    {
        $this->artisan('app:install-status')
            ->assertExitCode(0);
    }
}
