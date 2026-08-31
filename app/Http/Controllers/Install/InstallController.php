<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\Installer\InstallationState;
use App\Services\Installer\InstallerException;
use App\Services\Installer\InstallManager;
use App\Services\Installer\RequirementsChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/**
 * The WordPress-style first-run wizard.
 *
 * Screens: requirements → database → application → initialize (migrations
 * + reference data) → company → admin → finalize → complete.
 *
 * Every screen is stage-gated against InstallationState so the flow cannot
 * be skipped or replayed out of order, and every mutating action is
 * retry-safe.
 */
class InstallController extends Controller
{
    /** screen name => stage that must already be complete to view it */
    private const GATES = [
        'requirements' => null,
        'database' => 'requirements_ok',
        'application' => 'database_configured',
        'initialize' => 'database_configured',
        'company' => 'schema_initialized',
        'admin' => 'company_created',
        'finalize' => 'admin_created',
    ];

    public function __construct(
        private readonly InstallationState $state,
        private readonly InstallManager $manager,
    ) {
    }

    /* ----------------------------------------------------------------- */
    /* Entry point                                                      */
    /* ----------------------------------------------------------------- */

    public function index(): RedirectResponse
    {
        return redirect()->route('install.'.$this->screenForStage());
    }

    public function complete(): View|RedirectResponse
    {
        if (! $this->state->isLocked() && ! $this->state->isAtLeast('finalized')) {
            return redirect()->route('install.index');
        }

        return view('install.complete', [
            'loginUrl' => (string) config('installer.redirect_after', '/admin/login'),
            'appName' => config('app.name'),
        ]);
    }

    /* ----------------------------------------------------------------- */
    /* STEP 1 — Requirements                                            */
    /* ----------------------------------------------------------------- */

    public function requirements(RequirementsChecker $checker): View|RedirectResponse
    {
        if ($redirect = $this->gate('requirements')) {
            return $redirect;
        }

        return view('install.requirements', [
            'step' => $this->stepMeta('requirements'),
            'report' => $checker->check(),
        ]);
    }

    public function storeRequirements(RequirementsChecker $checker): RedirectResponse
    {
        if ($redirect = $this->gate('requirements')) {
            return $redirect;
        }

        if (! $checker->passes()) {
            return back()->with('installer_error', __('install.errors.requirements'));
        }

        $this->manager->markRequirementsPassed();

        return redirect()->route('install.database');
    }

    /* ----------------------------------------------------------------- */
    /* STEP 2 — Database                                               */
    /* ----------------------------------------------------------------- */

    public function database(): View|RedirectResponse
    {
        if ($redirect = $this->gate('database')) {
            return $redirect;
        }

        return view('install.database', [
            'step' => $this->stepMeta('database'),
            'old' => $this->state->data()['database'] ?? ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306],
        ]);
    }

    public function testDatabase(Request $request): JsonResponse
    {
        if ($this->gate('database')) {
            return response()->json(['ok' => false, 'message' => __('install.errors.generic')], 409);
        }

        $creds = $this->validateDatabase($request);

        try {
            $result = $this->manager->testDatabase($creds);
        } catch (Throwable $e) {
            $this->logSafely('installer.db_test', $e);
            $result = ['ok' => false, 'message' => __('install.errors.generic')];
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        if ($redirect = $this->gate('database')) {
            return $redirect;
        }

        $creds = $this->validateDatabase($request);

        try {
            $this->manager->configureDatabase($creds);
        } catch (InstallerException $e) {
            return back()->with('installer_error', $e->getMessage())->withInput($request->except('password'));
        } catch (Throwable $e) {
            $this->logSafely('installer.db_configure', $e);

            return back()->with('installer_error', __('install.errors.generic'))->withInput($request->except('password'));
        }

        return redirect()->route('install.application');
    }

    /* ----------------------------------------------------------------- */
    /* STEP 3 — Application                                            */
    /* ----------------------------------------------------------------- */

    public function application(): View|RedirectResponse
    {
        if ($redirect = $this->gate('application')) {
            return $redirect;
        }

        $data = $this->state->data()['application'] ?? [];

        return view('install.application', [
            'step' => $this->stepMeta('application'),
            'old' => [
                'name' => $data['name'] ?? config('app.name'),
                'url' => $data['url'] ?? $this->guessUrl(),
                'locale' => $data['locale'] ?? config('app.locale', 'fr'),
            ],
            'locales' => ['fr' => 'Français', 'en' => 'English', 'ar' => 'العربية'],
        ]);
    }

    public function storeApplication(Request $request): RedirectResponse
    {
        if ($redirect = $this->gate('application')) {
            return $redirect;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:255'],
            'locale' => ['required', Rule::in(['fr', 'en', 'ar'])],
        ]);

        try {
            $this->manager->configureApplication($data);
        } catch (Throwable $e) {
            $this->logSafely('installer.application', $e);

            return back()->with('installer_error', __('install.errors.generic'))->withInput();
        }

        return redirect()->route('install.initialize');
    }

    /* ----------------------------------------------------------------- */
    /* STEP 4 — Database initialization                                */
    /* ----------------------------------------------------------------- */

    public function initialize(): View|RedirectResponse
    {
        if ($redirect = $this->gate('initialize')) {
            return $redirect;
        }

        if ($this->state->isAtLeast('schema_initialized')) {
            return redirect()->route('install.company');
        }

        return view('install.initialize', [
            'step' => $this->stepMeta('initialize'),
        ]);
    }

    public function runInitialize(): RedirectResponse
    {
        if ($redirect = $this->gate('initialize')) {
            return $redirect;
        }

        try {
            $this->manager->initializeSchema();
        } catch (InstallerException $e) {
            return back()->with('installer_error', $e->getMessage());
        } catch (Throwable $e) {
            $this->logSafely('installer.initialize', $e);

            return back()->with('installer_error', __('install.errors.migrations_failed'));
        }

        return redirect()->route('install.company');
    }

    /* ----------------------------------------------------------------- */
    /* STEP 5 — First company                                          */
    /* ----------------------------------------------------------------- */

    public function company(): View|RedirectResponse
    {
        if ($redirect = $this->gate('company')) {
            return $redirect;
        }

        return view('install.company', [
            'step' => $this->stepMeta('company'),
            'old' => $this->state->data()['company'] ?? [],
        ]);
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        if ($redirect = $this->gate('company')) {
            return $redirect;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'ice' => ['nullable', 'string', 'max:50'],
            'rc' => ['nullable', 'string', 'max:50'],
            'if' => ['nullable', 'string', 'max:50'],
            'patente' => ['nullable', 'string', 'max:50'],
        ]);

        $data['locale'] = $this->state->data()['application']['locale'] ?? config('app.locale', 'fr');

        try {
            $this->manager->createCompany($data);
        } catch (InstallerException $e) {
            return back()->with('installer_error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            $this->logSafely('installer.company', $e);

            return back()->with('installer_error', __('install.errors.generic'))->withInput();
        }

        return redirect()->route('install.admin');
    }

    /* ----------------------------------------------------------------- */
    /* STEP 6 — Super administrator                                    */
    /* ----------------------------------------------------------------- */

    public function admin(): View|RedirectResponse
    {
        if ($redirect = $this->gate('admin')) {
            return $redirect;
        }

        return view('install.admin', [
            'step' => $this->stepMeta('admin'),
        ]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        if ($redirect = $this->gate('admin')) {
            return $redirect;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        $data['locale'] = $this->state->data()['application']['locale'] ?? config('app.locale', 'fr');

        try {
            $this->manager->createAdmin($data);
        } catch (InstallerException $e) {
            return back()->with('installer_error', $e->getMessage())->withInput($request->except(['password', 'password_confirmation']));
        } catch (Throwable $e) {
            $this->logSafely('installer.admin', $e);

            return back()
                ->with('installer_error', __('install.errors.generic'))
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        return redirect()->route('install.finalize');
    }

    /* ----------------------------------------------------------------- */
    /* STEP 7 — Finalization                                           */
    /* ----------------------------------------------------------------- */

    public function finalize(): View|RedirectResponse
    {
        if ($redirect = $this->gate('finalize')) {
            return $redirect;
        }

        return view('install.finalize', [
            'step' => $this->stepMeta('finalize'),
            'summary' => $this->state->data(),
        ]);
    }

    public function runFinalize(): RedirectResponse
    {
        if ($redirect = $this->gate('finalize')) {
            return $redirect;
        }

        try {
            $notes = $this->manager->finalize();
            session()->flash('installer_notes', $notes);
        } catch (Throwable $e) {
            $this->logSafely('installer.finalize', $e);

            return back()->with('installer_error', __('install.errors.finalize'));
        }

        return redirect()->route('install.complete');
    }

    /* ----------------------------------------------------------------- */
    /* Helpers                                                         */
    /* ----------------------------------------------------------------- */

    private function validateDatabase(Request $request): array
    {
        return $request->validate([
            'driver' => ['required', Rule::in(\App\Services\Installer\DatabaseConfigurator::DRIVERS)],
            'host' => ['required_unless:driver,sqlite', 'nullable', 'string', 'max:191'],
            'port' => ['required_unless:driver,sqlite', 'nullable', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:191'],
            'username' => ['required_unless:driver,sqlite', 'nullable', 'string', 'max:128'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** Redirect the visitor to the correct screen if they jumped ahead. */
    private function gate(string $screen): ?RedirectResponse
    {
        $needs = self::GATES[$screen] ?? null;

        if ($this->state->isAtLeast($needs)) {
            return null;
        }

        return redirect()->route('install.'.$this->screenForStage());
    }

    private function screenForStage(): string
    {
        return match ($this->state->stage()) {
            null => 'requirements',
            'requirements_ok' => 'database',
            'database_configured' => isset($this->state->data()['application']) ? 'initialize' : 'application',
            'schema_initialized' => 'company',
            'company_created' => 'admin',
            'admin_created' => 'finalize',
            'finalized' => 'complete',
            default => 'requirements',
        };
    }

    private function stepMeta(string $screen): array
    {
        $order = ['requirements', 'database', 'application', 'initialize', 'company', 'admin', 'finalize'];
        $index = (int) array_search($screen, $order, true);

        return [
            'key' => $screen,
            'number' => $index + 1,
            'total' => count($order),
            'labels' => $order,
        ];
    }

    private function guessUrl(): string
    {
        return rtrim((string) (request()->getSchemeAndHttpHost() ?: config('app.url')), '/');
    }

    private function logSafely(string $channel, Throwable $e): void
    {
        // Message only — never the exception context, which could contain
        // connection arrays / bindings with the DB or admin password.
        Log::warning('['.$channel.'] '.class_basename($e).': '.$this->scrub($e->getMessage()));
    }

    private function scrub(string $message): string
    {
        // Defensive: strip anything that looks like a DSN password fragment.
        return preg_replace('/(password=)[^;\s]+/i', '$1***', $message) ?? $message;
    }
}
