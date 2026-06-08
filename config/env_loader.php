<?php
/**
 * Lightweight .env loader for dev/prod separation without Composer.
 * Loads .env by default and .env.production when APP_ENV === 'production'.
 */
class EnvLoader {
    private static $loaded = [];

    private static function parseEnvFile(string $path, bool $override = false): void {
        if (!is_file($path) || !is_readable($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            // Simple KEY=VALUE parser (no multiline, no quotes escaping beyond basic)
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if ($override || (!array_key_exists($key, $_ENV))) {
                $_ENV[$key] = $val;
                // Also expose via putenv for getenv()
                @putenv($key . '=' . $val);
            }
        }
        self::$loaded[] = $path;
    }

    public static function loadDefaults(): void {
        $root = dirname(__DIR__);
        $defaultEnv = $root . DIRECTORY_SEPARATOR . '.env';
        self::parseEnvFile($defaultEnv, false);
    }

    public static function loadForAppEnv(): void {
        $root = dirname(__DIR__);
        $env = defined('APP_ENV') ? APP_ENV : ($_ENV['APP_ENV'] ?? 'development');
        if ($env === 'production') {
            $prodEnv = $root . DIRECTORY_SEPARATOR . '.env.production';
            self::parseEnvFile($prodEnv, true); // override with production values
        }
    }

    public static function getLoadedFiles(): array {
        return self::$loaded;
    }
}
