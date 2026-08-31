<?php

namespace Tests\Feature\Installer;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * THE REAL CLEAN-DATABASE TEST.
 *
 * Runs the entire wizard over HTTP against a disposable MySQL database
 * (gestion_app_installer_test) that this test creates and drops. Covers:
 *
 *   SCENARIO A — clean install end to end
 *   SCENARIO B — invalid database credentials
 *   SCENARIO C — partial failure / retry is idempotent
 *   SCENARIO D — /install is gone once locked
 *   SCENARIO F — security (password hashing, no secret leakage, prod flags)
 *
 * It does NOT use RefreshDatabase and never touches the developer's .env or
 * the gestion_app / gestion_app_test databases.
 */
class InstallerEndToEndTest extends TestCase
{
    private const DISPOSABLE_DB = 'gestion_app_installer_test';

    private string $tmp;
    private string $tmpEnv;
    private array $originalMysql;
    private string $originalDefault;

    private array $dbCreds;
    private string $adminPassword = 'Sup3r-Str0ng-Pass';

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefault = (string) config('database.default');
        $this->originalMysql = (array) config('database.connections.mysql');

        $this->dbCreds = [
            'driver' => 'mysql',
            'host' => $this->originalMysql['host'] ?? '127.0.0.1',
            'port' => (int) ($this->originalMysql['port'] ?? 3306),
            'database' => self::DISPOSABLE_DB,
            'username' => $this->originalMysql['username'] ?? 'root',
            'password' => (string) ($this->originalMysql['password'] ?? ''),
        ];

        // Fresh disposable schema.
        DB::connection('mysql')->statement('DROP DATABASE IF EXISTS `'.self::DISPOSABLE_DB.'`');
        DB::connection('mysql')->statement('CREATE DATABASE `'.self::DISPOSABLE_DB.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Isolated installer state + throwaway .env.
        $this->tmp = storage_path('framework/testing/installer-e2e-'.uniqid());
        File::ensureDirectoryExists($this->tmp);
        $this->tmpEnv = $this->tmp.'/.env';
        File::put($this->tmpEnv, implode("\n", [
            'APP_NAME="Seed Name"',
            'APP_ENV=testing',
            'APP_KEY='.config('app.key'),
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            'APP_LOCALE=en',
            'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1',
            'DB_PORT=3306',
            'DB_DATABASE=',
            'DB_USERNAME=',
            'DB_PASSWORD=',
            '',
        ]));

        config([
            'installer.enable_in_tests' => true,
            'installer.trust_schema' => false,
            'installer.run_optimizations' => false,
            'installer.lock_path' => $this->tmp.'/installed',
            'installer.state_path' => $this->tmp.'/state',
            'installer.env_path' => $this->tmpEnv,
        ]);
    }

    protected function tearDown(): void
    {
        try {
            DB::purge('mysql');
            config(['database.connections.mysql' => $this->originalMysql]);
            DB::setDefaultConnection($this->originalDefault);
            DB::connection('mysql')->statement('DROP DATABASE IF EXISTS `'.self::DISPOSABLE_DB.'`');
            DB::purge('mysql');
        } catch (\Throwable) {
            // best effort
        }

        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    #[Test]
    public function it_installs_the_application_from_an_empty_database(): void
    {
        // ── SCENARIO A: routing before install ──────────────────────────
        $this->get('/')->assertRedirect('/install');

        // STEP 1 — requirements
        $this->get('/install/requirements')->assertOk();
        $this->post('/install/requirements')->assertRedirect(route('install.database'));

        // ── SCENARIO B: invalid credentials, mid-flow ──────────────────
        $bad = $this->post('/install/database', array_merge($this->dbCreds, [
            'password' => 'definitely-wrong-'.uniqid(),
        ]));
        $bad->assertRedirect(); // back to the database screen
        $bad->assertSessionHas('installer_error');
        $this->assertFileDoesNotExist($this->tmp.'/installed');
        $this->assertSame('requirements_ok', $this->stage());

        // Connection test endpoint also reports failure cleanly, no secrets.
        $probe = $this->postJson('/install/database/test', array_merge($this->dbCreds, [
            'password' => 'still-wrong',
        ]));
        $probe->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertStringNotContainsString('still-wrong', $probe->getContent());

        // STEP 2 — valid credentials
        $this->postJson('/install/database/test', $this->dbCreds)
            ->assertOk()->assertJson(['ok' => true]);
        $this->post('/install/database', $this->dbCreds)
            ->assertRedirect(route('install.application'));
        $this->assertSame('database_configured', $this->stage());
        $this->assertSame('mysql', (new \App\Support\EnvFile($this->tmpEnv))->get('DB_CONNECTION'));
        $this->assertSame(self::DISPOSABLE_DB, (new \App\Support\EnvFile($this->tmpEnv))->get('DB_DATABASE'));

        // STEP 3 — application
        $this->post('/install/application', [
            'name' => 'Acme ERP',
            'url' => 'https://erp.acme.test',
            'locale' => 'fr',
        ])->assertRedirect(route('install.initialize'));

        // STEP 4 — schema init (migrations + platform reference data)
        $this->post('/install/initialize')->assertRedirect(route('install.company'));
        $this->assertSame('schema_initialized', $this->stage());

        $conn = DB::connection('mysql');
        $this->assertGreaterThan(100, $conn->table('migrations')->count());
        $this->assertSame(34, $conn->table('permissions')->count());
        $this->assertGreaterThanOrEqual(9, $conn->table('roles')->count());

        // STEP 5 — first company
        $this->post('/install/company', [
            'name' => 'Acme Morocco SARL',
            'phone' => '+212600000000',
            'email' => 'contact@acme.test',
            'city' => 'Casablanca',
            'ice' => '001234567000089',
        ])->assertRedirect(route('install.admin'));
        $this->assertSame('company_created', $this->stage());
        $this->assertSame(1, $conn->table('companies')->count());
        $this->assertGreaterThan(0, $conn->table('document_types')->count());

        // ── SCENARIO C: replay STEP 5 → gate redirects, no duplication ──
        $this->post('/install/company', ['name' => 'Acme Morocco SARL'])
            ->assertRedirect(route('install.admin'));
        $this->assertSame(1, $conn->table('companies')->count());
        $this->assertGreaterThanOrEqual(9, $conn->table('roles')->count());

        // STEP 6 — super admin
        $this->post('/install/admin', [
            'name' => 'Amine Admin',
            'email' => 'admin@acme.test',
            'password' => $this->adminPassword,
            'password_confirmation' => $this->adminPassword,
        ])->assertRedirect(route('install.finalize'));
        $this->assertSame('admin_created', $this->stage());

        // STEP 7 — finalize
        $done = $this->post('/install/finalize');
        $done->assertRedirect(route('install.complete'));
        $this->get('/install/complete')->assertOk()->assertSee(__('install.complete.heading'));

        // ── SCENARIO D: installer is gone ──────────────────────────────
        $this->assertFileExists($this->tmp.'/installed');
        $this->get('/install')->assertNotFound();
        $this->get('/install/requirements')->assertNotFound();
        $this->get('/install/admin')->assertNotFound();

        // ── SCENARIO F: security / correctness assertions ──────────────
        $admin = User::on('mysql')->where('email', 'admin@acme.test')->firstOrFail();
        $this->assertNotSame($this->adminPassword, $admin->password);
        $this->assertTrue(Hash::check($this->adminPassword, $admin->password));
        $this->assertTrue($admin->hasRole('Super Admin'));

        $company = Company::on('mysql')->firstOrFail();
        $this->assertDatabaseHas('company_user', [
            'user_id' => $admin->id,
            'company_id' => $company->id,
        ], 'mysql');

        // Super Admin role holds every permission.
        $this->assertSame(
            $conn->table('permissions')->count(),
            $conn->table('role_has_permissions')
                ->where('role_id', $conn->table('roles')->where('name', 'Super Admin')->value('id'))
                ->count()
        );

        // Production hardening landed in the throwaway .env.
        $env = new \App\Support\EnvFile($this->tmpEnv);
        $this->assertSame('production', $env->get('APP_ENV'));
        $this->assertSame('false', $env->get('APP_DEBUG'));
        $this->assertSame('https://erp.acme.test', $env->get('APP_URL'));

        // No credential ever appears in a rendered response.
        $this->assertStringNotContainsString($this->adminPassword, $done->getContent() ?: '');
        $complete = $this->get('/install/complete')->getContent();
        $this->assertStringNotContainsString($this->adminPassword, $complete);

        // Counts summary (spec asks for these to be shown).
        fwrite(STDERR, sprintf(
            "\n[installer e2e] migrations=%d users=%d companies=%d roles=%d permissions=%d document_types=%d settings=%d\n",
            $conn->table('migrations')->count(),
            $conn->table('users')->count(),
            $conn->table('companies')->count(),
            $conn->table('roles')->count(),
            $conn->table('permissions')->count(),
            $conn->table('document_types')->count(),
            $conn->table('settings')->count(),
        ));
    }

    private function stage(): ?string
    {
        $file = $this->tmp.'/state/state.json';
        if (! is_file($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), true)['stage'] ?? null;
    }
}
