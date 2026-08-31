<?php

namespace App\Support;

use RuntimeException;

/**
 * Safe, non-destructive reader/writer for a Laravel `.env` file.
 *
 * Design goals (see installer spec):
 *  - Never corrupt the file: comments, blank lines and key order are preserved.
 *  - Update-in-place when a key exists, append at the end when it does not.
 *  - Collapse duplicate keys: the first occurrence is updated, later ones removed.
 *  - Correctly quote values containing spaces, '#', '"', '\', '=' or that are empty
 *    when they previously were quoted.
 *  - Tolerate CRLF or LF line endings on input; always writes LF.
 *  - Atomic write (temp file + rename) so a crash mid-write can't truncate .env.
 *  - Values are treated as sensitive: this class never logs or echoes them.
 */
final class EnvFile
{
    public function __construct(private readonly string $path)
    {
    }

    public static function make(?string $path = null): self
    {
        return new self(
            $path
                ?? config('installer.env_path')
                ?? app()->environmentFilePath()
        );
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * Return the raw (unquoted) value for a key, or $default when absent.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        foreach ($this->lines() as $line) {
            $parsed = $this->parseLine($line);
            if ($parsed !== null && $parsed['key'] === $key) {
                return $this->unquote($parsed['value']);
            }
        }

        return $default;
    }

    public function has(string $key): bool
    {
        foreach ($this->lines() as $line) {
            $parsed = $this->parseLine($line);
            if ($parsed !== null && $parsed['key'] === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge the given key => value pairs into the file and persist atomically.
     *
     * A null value removes the key entirely. Boolean values are written as
     * `true` / `false`. Everything else is stringified and quoted only when
     * necessary.
     */
    public function write(array $values): void
    {
        if (! $this->exists()) {
            throw new RuntimeException('Environment file not found: '.$this->path);
        }

        if (! is_writable($this->path)) {
            throw new RuntimeException('Environment file is not writable: '.$this->path);
        }

        $lines = $this->lines();
        $seen = [];

        foreach ($lines as $i => $line) {
            $parsed = $this->parseLine($line);
            if ($parsed === null) {
                continue;
            }

            $key = $parsed['key'];

            if (! array_key_exists($key, $values)) {
                continue;
            }

            // Duplicate key later in the file → drop it, first wins.
            if (isset($seen[$key])) {
                $lines[$i] = "\0__ENV_DROP__\0";

                continue;
            }

            $seen[$key] = true;

            if ($values[$key] === null) {
                $lines[$i] = "\0__ENV_DROP__\0";

                continue;
            }

            $wasQuoted = str_starts_with(trim($parsed['value']), '"')
                || str_starts_with(trim($parsed['value']), "'");

            $lines[$i] = $key.'='.$this->formatValue($values[$key], $wasQuoted);
        }

        $lines = array_values(array_filter(
            $lines,
            static fn ($l) => $l !== "\0__ENV_DROP__\0"
        ));

        // Append keys that were not present anywhere in the file, directly
        // after the existing content (no extra blank line).
        foreach ($values as $key => $value) {
            if (isset($seen[$key]) || $value === null) {
                continue;
            }

            $lines[] = $key.'='.$this->formatValue($value, false);
        }

        $this->put(implode("\n", $lines)."\n");
    }

    /* --------------------------------------------------------------------- */

    /** @return array<int,string> */
    private function lines(): array
    {
        $raw = file_get_contents($this->path);
        if ($raw === false) {
            throw new RuntimeException('Unable to read environment file: '.$this->path);
        }

        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = explode("\n", $raw);

        // Drop a single trailing empty element produced by the final newline.
        if ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * @return array{key:string,value:string}|null  null for comments / blanks / malformed
     */
    private function parseLine(string $line): ?array
    {
        $trimmed = ltrim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            return null;
        }

        if (! preg_match('/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_.]*)\s*=(.*)$/s', $line, $m)) {
            return null;
        }

        return ['key' => $m[1], 'value' => $m[2]];
    }

    private function unquote(string $value): string
    {
        $value = trim($value);

        if (strlen($value) >= 2
            && ($value[0] === '"' || $value[0] === "'")
            && $value[strlen($value) - 1] === $value[0]
        ) {
            $inner = substr($value, 1, -1);

            return $value[0] === '"'
                ? stripcslashes($inner)
                : $inner;
        }

        // Strip a trailing inline comment on unquoted values ( FOO=bar # note ).
        if (($hash = strpos($value, ' #')) !== false) {
            $value = rtrim(substr($value, 0, $hash));
        }

        return $value;
    }

    private function formatValue(mixed $value, bool $forceQuote): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        $mustQuote = $forceQuote
            || $value === ''
            || preg_match('/[\s"#\'=\\\\$]/', $value) === 1
            || str_starts_with($value, ' ')
            || str_ends_with($value, ' ');

        if (! $mustQuote) {
            return $value;
        }

        $escaped = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', ''], $value);

        return '"'.$escaped.'"';
    }

    private function put(string $contents): void
    {
        $dir = dirname($this->path);
        $tmp = tempnam($dir, 'env');

        if ($tmp === false) {
            throw new RuntimeException('Unable to create temporary file in '.$dir);
        }

        try {
            if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write temporary environment file.');
            }

            @chmod($tmp, 0640);

            // rename() is atomic on the same filesystem; on Windows it fails when
            // the destination exists, so fall back to a copy+unlink there.
            if (! @rename($tmp, $this->path)) {
                if (file_put_contents($this->path, $contents, LOCK_EX) === false) {
                    throw new RuntimeException('Unable to persist environment file.');
                }
                @unlink($tmp);
            }
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }
    }
}
