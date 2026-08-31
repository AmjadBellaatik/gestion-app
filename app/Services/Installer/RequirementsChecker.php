<?php

namespace App\Services\Installer;

use Throwable;

/**
 * Builds the data for STEP 1 (Welcome / Requirements).
 *
 * The minimum PHP version is derived from composer.json ("require"."php")
 * rather than hard-coded, per the installer spec.
 */
class RequirementsChecker
{
    /**
     * @return array{
     *   ok: bool,
     *   php: array{current:string,required:string,ok:bool},
     *   extensions: array<int,array{name:string,required:bool,loaded:bool}>,
     *   writable: array<int,array{path:string,ok:bool}>,
     *   database_drivers: array<int,array{name:string,loaded:bool}>,
     *   app_name: string,
     * }
     */
    public function check(): array
    {
        $php = $this->checkPhp();
        $extensions = $this->checkExtensions();
        $writable = $this->checkWritable();
        $drivers = $this->databaseDrivers();

        $mandatoryOk = $php['ok']
            && collect($extensions)->every(fn ($e) => ! $e['required'] || $e['loaded'])
            && collect($writable)->every(fn ($w) => $w['ok'])
            && collect($drivers)->contains(fn ($d) => $d['loaded']);

        return [
            'ok' => $mandatoryOk,
            'php' => $php,
            'extensions' => $extensions,
            'writable' => $writable,
            'database_drivers' => $drivers,
            'app_name' => (string) config('app.name', 'Application'),
        ];
    }

    public function passes(): bool
    {
        return $this->check()['ok'];
    }

    /* ----------------------------------------------------------------- */

    private function checkPhp(): array
    {
        $required = $this->requiredPhpConstraint();
        $current = PHP_VERSION;

        // Take the lowest floor across all "||"-separated ranges as the
        // effective minimum (e.g. "^8.3", ">= 8.4.0", "8.2.*|8.3.*").
        $min = null;
        if (preg_match_all('/(\d+\.\d+(?:\.\d+)?)/', $required, $m)) {
            foreach ($m[1] as $candidate) {
                if ($min === null || version_compare($candidate, $min, '<')) {
                    $min = $candidate;
                }
            }
        }

        $ok = $min === null
            ? true
            : version_compare($current, $min, '>=');

        return [
            'current' => $current,
            'required' => $min ? $required.'  (min '.$min.')' : $required,
            'ok' => $ok,
        ];
    }

    private function requiredPhpConstraint(): string
    {
        try {
            $composer = json_decode(
                (string) file_get_contents(base_path('composer.json')),
                true,
                flags: JSON_THROW_ON_ERROR
            );

            $constraint = $composer['require']['php'] ?? null;

            if (is_string($constraint) && $constraint !== '') {
                return $constraint;
            }
        } catch (Throwable) {
            // ignore
        }

        return '^8.2';
    }

    private function checkExtensions(): array
    {
        $cfg = (array) config('installer.requirements.php_extensions', []);
        $out = [];

        foreach (($cfg['required'] ?? []) as $name) {
            $out[] = ['name' => $name, 'required' => true, 'loaded' => \extension_loaded($name)];
        }

        foreach (($cfg['optional'] ?? []) as $name) {
            $out[] = ['name' => $name, 'required' => false, 'loaded' => \extension_loaded($name)];
        }

        return $out;
    }

    private function checkWritable(): array
    {
        $paths = (array) config('installer.requirements.writable_paths', []);
        $out = [];

        foreach ($paths as $relative) {
            $abs = base_path($relative);
            $out[] = [
                'path' => $relative,
                'ok' => is_dir($abs) && is_writable($abs),
            ];
        }

        // The .env file itself must be writable for STEP 2/3/7.
        $envPath = app()->environmentFilePath();
        $out[] = [
            'path' => '.env',
            'ok' => is_file($envPath) && is_writable($envPath),
        ];

        return $out;
    }

    private function databaseDrivers(): array
    {
        return [
            ['name' => 'MySQL / MariaDB (pdo_mysql)', 'loaded' => \extension_loaded('pdo_mysql')],
            ['name' => 'PostgreSQL (pdo_pgsql)', 'loaded' => \extension_loaded('pdo_pgsql')],
            ['name' => 'SQLite (pdo_sqlite)', 'loaded' => \extension_loaded('pdo_sqlite')],
        ];
    }
}
