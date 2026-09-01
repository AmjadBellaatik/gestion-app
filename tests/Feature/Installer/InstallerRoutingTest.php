<?php

namespace Tests\Feature\Installer;

use App\Services\Installer\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SCENARIO A (routing) + SCENARIO D (installed → installer gone) +
 * "installer is inert in the test environment unless explicitly enabled".
 */
class InstallerRoutingTest extends TestCase
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
            'installer.trust_schema' => false, // pure lock-file semantics for this test
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    #[Test]
    public function while_not_installed_normal_routes_redirect_to_the_installer(): void
    {
        config(['installer.enable_in_tests' => true]);

        $this->get('/')->assertRedirect('/install');
        $this->get('/admin')->assertRedirect('/install');
        $this->get('/admin/login')->assertRedirect('/install');
    }

    #[Test]
    public function the_installer_landing_page_renders_while_not_installed(): void
    {
        config(['installer.enable_in_tests' => true]);

        $this->get('/install')->assertRedirect(route('install.requirements'));
        $this->get('/install/requirements')
            ->assertOk()
            ->assertSee(__('install.requirements.heading'));
    }

    #[Test]
    public function once_locked_the_installer_disappears_and_the_app_boots(): void
    {
        config(['installer.enable_in_tests' => true]);
        app(InstallationState::class)->markCompleted('test');

        // Bare entrypoint + success page redirect to login; step pages 404.
        $this->get('/install')->assertRedirect(config('installer.redirect_after'));
        $this->get('/install/complete')->assertRedirect(config('installer.redirect_after'));
        $this->get('/install/requirements')->assertNotFound();
        $this->get('/install/database')->assertNotFound();
        $this->get('/install/company')->assertNotFound();
        $this->post('/install/company')->assertNotFound();

        // Normal routing resumes (/, closure, redirects to /admin).
        $this->get('/')->assertRedirect('/admin');
    }

    #[Test]
    public function the_guard_is_inert_in_the_test_environment_by_default(): void
    {
        // enable_in_tests defaults to false → existing feature tests are
        // never redirected to /install even though there is no lock file.
        config(['installer.enable_in_tests' => false]);

        $this->get('/')->assertRedirect('/admin');   // unchanged legacy behaviour
        $this->get('/install')->assertNotFound();     // installer hidden in tests
    }

    #[Test]
    public function installer_routes_carry_csrf_and_the_block_guard(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'install/database');

        $this->assertContains('installer', $route->gatherMiddleware());

        $group = app('router')->getMiddlewareGroups()['installer'] ?? [];

        $this->assertContains(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $group);
        $this->assertContains(\App\Http\Middleware\BlockWhenInstalled::class, $group);
        $this->assertContains(\App\Http\Middleware\PrepareInstaller::class, $group);
        $this->assertContains(\Illuminate\Session\Middleware\StartSession::class, $group);
    }
}
