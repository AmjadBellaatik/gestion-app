<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;

/**
 * Generates and installs an APP_KEY without shelling out to `artisan
 * key:generate`, and — crucially — rebinds the live encrypter so the
 * current request (the installer's first hit on a fresh clone) can use
 * sessions, CSRF and encrypted cookies immediately.
 */
final class AppKey
{
    /**
     * Ensure a usable APP_KEY exists. Returns true when a new key was
     * generated, false when a valid one was already present.
     *
     * An existing, valid key is NEVER overwritten (protects live installs).
     */
    public static function ensure(): bool
    {
        if (self::isValid((string) config('app.key'))) {
            return false;
        }

        self::regenerate();

        return true;
    }

    public static function regenerate(): string
    {
        $cipher = (string) config('app.cipher', 'AES-256-CBC');
        $key = 'base64:'.base64_encode(Encrypter::generateKey($cipher));

        EnvFile::make()->write(['APP_KEY' => $key]);

        // Live config + service rebind for the current process.
        config(['app.key' => $key]);

        app()->forgetInstance('encrypter');
        if (app()->bound(Encrypter::class)) {
            app()->forgetInstance(Encrypter::class);
        }

        return $key;
    }

    public static function isValid(string $key): bool
    {
        if ($key === '') {
            return false;
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            return $decoded !== false && in_array(strlen($decoded), [16, 24, 32], true);
        }

        return in_array(strlen($key), [16, 24, 32], true);
    }
}
