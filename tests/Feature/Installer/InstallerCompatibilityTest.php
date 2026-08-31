<?php

namespace Tests\Feature\Installer;

use App\Models\Company;
use App\Models\User;
use App\Services\Installer\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
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
    public function a_migrated_database_is_treated_as_installed_and_self_heals_the_lock(): void
    {
        // RefreshDatabase has populated the `migrations` table and APP_KEY is
        // set → this looks exactly like a legacy deployment pulling the code.
        $this->assertFileDoesNotExist($this->tmp.'/installed');

        $this->assertTrue(app(InstallationState::class)->isInstalled());
        $this->assertFileExists($this->tmp.'/installed'); // healed

        $this->get('/install')->assertNotFound();
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
        $this->get('/install')->assertNotFound();
    }

    #[Test]
    public function install_status_command_reports_health(): void
    {
        $this->artisan('app:install-status')
            ->assertExitCode(0);
    }
}
