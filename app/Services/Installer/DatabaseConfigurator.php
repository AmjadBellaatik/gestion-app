<?php

namespace App\Services\Installer;

use App\Support\EnvFile;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * STEP 2 — database configuration.
 *
 *  - validate() sanity-checks user input without touching the network.
 *  - test()     opens a throw-away PDO connection with the supplied
 *               credentials and returns a friendly result (never a stack
 *               trace, never the password).
 *  - persist()  writes the DB_* keys into .env via the safe EnvFile writer
 *               and rebinds the live connection for the current request.
 */
class DatabaseConfigurator
{
    public const DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlite'];

    /** @return array<int,string> list of human-readable validation errors */
    public function validate(array $creds): array
    {
        $errors = [];

        $driver = $creds['driver'] ?? 'mysql';
        if (! in_array($driver, self::DRIVERS, true)) {
            $errors[] = __('install.errors.driver');
        }

        if ($driver === 'sqlite') {
            $database = (string) ($creds['database'] ?? '');
            if ($database === '' || preg_match('#[/\\\\]#', $database) || str_contains($database, '..')) {
                $errors[] = __('install.errors.sqlite_path');
            }

            return $errors;
        }

        $host = (string) ($creds['host'] ?? '');
        if ($host === '' || ! preg_match('/^[A-Za-z0-9._\-]+$/', $host)) {
            $errors[] = __('install.errors.host');
        }

        $port = (int) ($creds['port'] ?? 0);
        if ($port < 1 || $port > 65535) {
            $errors[] = __('install.errors.port');
        }

        $database = (string) ($creds['database'] ?? '');
        if ($database === '' || ! preg_match('/^[A-Za-z0-9_.\-]{1,64}$/', $database)) {
            $errors[] = __('install.errors.database');
        }

        $username = (string) ($creds['username'] ?? '');
        if ($username === '' || strlen($username) > 128 || preg_match('/[\x00-\x1F]/', $username)) {
            $errors[] = __('install.errors.username');
        }

        return $errors;
    }

    /**
     * @return array{ok:bool,message:string,server_version?:string,database_exists?:bool}
     */
    public function test(array $creds): array
    {
        $errors = $this->validate($creds);
        if ($errors !== []) {
            return ['ok' => false, 'message' => implode(' ', $errors)];
        }

        $driver = $creds['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                return ['ok' => true, 'message' => __('install.database.sqlite_ready'), 'database_exists' => true];
            }

            $pdoDriver = $driver === 'mariadb' ? 'mysql' : $driver;
            $host = $creds['host'];
            $port = (int) $creds['port'];
            $database = $creds['database'];
            $username = $creds['username'];
            $password = (string) ($creds['password'] ?? '');

            // 1) Connect to the server itself (no database selected) so a
            //    "database does not exist yet" situation is reported clearly
            //    rather than as a hard failure.
            $serverDsn = $pdoDriver === 'pgsql'
                ? "pgsql:host={$host};port={$port}"
                : "mysql:host={$host};port={$port}";

            $pdo = new PDO($serverDsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            $version = (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

            $exists = $this->databaseExists($pdo, $pdoDriver, $database);

            return [
                'ok' => true,
                'message' => $exists
                    ? __('install.database.connection_ok')
                    : __('install.database.connection_ok_will_create'),
                'server_version' => $version,
                'database_exists' => $exists,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $this->friendlyError($e)];
        }
    }

    /**
     * Create the target schema if the server allows it. Safe to call twice.
     */
    public function ensureDatabaseExists(array $creds): void
    {
        $driver = $creds['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            $path = database_path((string) $creds['database']);
            if (! is_file($path)) {
                @touch($path);
            }

            return;
        }

        $pdoDriver = $driver === 'mariadb' ? 'mysql' : $driver;
        $host = $creds['host'];
        $port = (int) $creds['port'];
        $database = $creds['database'];

        if ($pdoDriver === 'pgsql') {
            // Creating a pgsql database mid-transaction is awkward; assume the
            // operator created it. A clear error surfaces later otherwise.
            return;
        }

        $pdo = new PDO("mysql:host={$host};port={$port}", $creds['username'], (string) ($creds['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        $quoted = str_replace('`', '``', $database);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$quoted}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Write DB_* into .env and rebind the live connection so subsequent
     * stages (migrations, seeding) use it within the same request.
     */
    public function persist(array $creds): void
    {
        $driver = $creds['driver'] ?? 'mysql';
        $connection = $driver === 'mariadb' ? 'mysql' : $driver;

        $env = EnvFile::make();
        $env->write([
            'DB_CONNECTION' => $connection,
            'DB_HOST' => $driver === 'sqlite' ? null : $creds['host'],
            'DB_PORT' => $driver === 'sqlite' ? null : (string) (int) $creds['port'],
            'DB_DATABASE' => $creds['database'],
            'DB_USERNAME' => $driver === 'sqlite' ? null : $creds['username'],
            'DB_PASSWORD' => $driver === 'sqlite' ? null : (string) ($creds['password'] ?? ''),
        ]);

        // Runtime rebind for the current process.
        config([
            "database.default" => $connection,
            "database.connections.{$connection}.driver" => $connection === 'mariadb' ? 'mysql' : $connection,
            "database.connections.{$connection}.host" => $creds['host'] ?? '127.0.0.1',
            "database.connections.{$connection}.port" => (string) ($creds['port'] ?? 3306),
            "database.connections.{$connection}.database" => $creds['database'],
            "database.connections.{$connection}.username" => $creds['username'] ?? '',
            "database.connections.{$connection}.password" => (string) ($creds['password'] ?? ''),
        ]);

        DB::purge($connection);
        DB::reconnect($connection);
        DB::setDefaultConnection($connection);
    }

    public function currentConnectionWorks(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /* ----------------------------------------------------------------- */

    private function databaseExists(PDO $pdo, string $driver, string $database): bool
    {
        try {
            if ($driver === 'pgsql') {
                $stmt = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
            } else {
                $stmt = $pdo->prepare('SELECT 1 FROM information_schema.schemata WHERE schema_name = ?');
            }
            $stmt->execute([$database]);

            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Map raw PDO exceptions to short, credential-free messages.
     */
    private function friendlyError(Throwable $e): string
    {
        $code = $e instanceof \PDOException ? ($e->getCode() ?: null) : null;
        $raw = strtolower($e->getMessage());

        return match (true) {
            str_contains($raw, 'access denied') || $code === 1045 => __('install.errors.access_denied'),
            str_contains($raw, 'unknown database') || $code === 1049 => __('install.errors.unknown_database'),
            str_contains($raw, "can't connect") || str_contains($raw, 'connection refused')
                || str_contains($raw, 'actively refused') || $code === 2002 => __('install.errors.connection_refused'),
            str_contains($raw, 'timed out') || str_contains($raw, 'timeout') => __('install.errors.timeout'),
            str_contains($raw, 'getaddrinfo') || str_contains($raw, 'name or service not known')
                || str_contains($raw, 'no such host') => __('install.errors.host_unresolved'),
            default => __('install.errors.generic'),
        };
    }
}
