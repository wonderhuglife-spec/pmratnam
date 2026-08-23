<?php
declare(strict_types=1);

/**
 * Load .env into getenv/$_ENV. Hostinger shared hosting does not inject env vars.
 */
function pmr_load_env(string $basePath, bool $force = false): void
{
    static $loaded = false;
    if ($loaded && !$force) {
        return;
    }
    $loaded = true;

    $file = $basePath . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
            $value = str_replace(['\\"', '\\\\'], ['"', '\\'], $value);
        }
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function pmr_env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}
