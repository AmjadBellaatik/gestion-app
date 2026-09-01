<?php

namespace Tests\Unit\Installer;

use App\Services\Installer\InstallationState;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pure state-machine behaviour (no database): the fresh / in-progress /
 * installed distinction and the premature-lock guard.
 */
class InstallationStateTest extends TestCase
{
    private string $tmp;
    private InstallationState $state;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = storage_path('framework/testing/state-'.uniqid());
        File::ensureDirectoryExists($this->tmp);

        config([
            'installer.lock_path' => $this->tmp.'/installed',
            'installer.state_path' => $this->tmp.'/state',
            'installer.trust_schema' => false, // isolate from any DB
        ]);

        $this->state = app(InstallationState::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    #[Test]
    public function a_fresh_install_is_neither_in_progress_nor_installed(): void
    {
        $this->assertFalse($this->state->isInProgress());
        $this->assertFalse($this->state->isLocked());
        $this->assertFalse($this->state->isInstalled());
    }

    #[Test]
    public function every_non_final_stage_counts_as_in_progress(): void
    {
        foreach (['requirements_ok', 'database_configured', 'schema_initialized', 'company_created', 'admin_created'] as $stage) {
            $this->state->resetTransient();
            $this->state->complete($stage);

            $this->assertTrue($this->state->isInProgress(), "stage {$stage}");
            $this->assertFalse($this->state->isInstalled(), "stage {$stage}");
        }
    }

    #[Test]
    public function the_finalized_stage_is_not_in_progress(): void
    {
        $this->state->complete('finalized');

        $this->assertFalse($this->state->isInProgress());
    }

    #[Test]
    public function a_lock_written_while_a_stage_is_active_does_not_mark_the_app_installed(): void
    {
        $this->state->complete('schema_initialized');
        $this->state->markInstalled('premature bug');

        $this->assertTrue($this->state->isLocked());
        $this->assertTrue($this->state->isInProgress());
        $this->assertFalse($this->state->isInstalled(), 'in-progress must beat a stray lock');
    }

    #[Test]
    public function a_lock_with_no_transient_state_means_installed(): void
    {
        $this->state->markCompleted();

        $this->assertFalse($this->state->isInProgress());
        $this->assertTrue($this->state->isInstalled());
    }

    #[Test]
    public function finalize_sequence_ends_up_installed(): void
    {
        $this->state->complete('admin_created');
        $this->state->markCompleted();
        $this->state->complete('finalized');
        $this->state->resetTransient();

        $this->assertFalse($this->state->isInProgress());
        $this->assertTrue($this->state->isInstalled());
    }
}
