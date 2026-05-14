<?php
/**
 * Tiny zero-dependency .env loader.
 *
 * Reads <project-root>/.env (one level up from /config) on first call,
 * caches the result, and exposes an env() helper.
 *
 * Usage:
 *   require_once __DIR__ . '/env.php';
 *   $key = env('RECAPTCHA_SECRET_KEY', '');
 */

if (!function_exists('loadEnv')) {
    /**
     * Parse the .env file at the project root and return [KEY => value].
     * Returns an empty array if .env is missing or unreadable.
     */
    function loadEnv(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];

        $path = __DIR__ . '/../.env';
        if (!is_readable($path)) {
            return $cache;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $cache;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            if ($k === '') {
                continue;
            }
            // Strip matching surrounding quotes if present
            $len = strlen($v);
            if ($len >= 2) {
                $first = $v[0];
                $last  = $v[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $v = substr($v, 1, -1);
                }
            }
            $cache[$k] = $v;
        }
        return $cache;
    }
}

if (!function_exists('env')) {
    /**
     * Resolve an env variable: .env file first, then process env, then default.
     */
    function env(string $key, ?string $default = null): ?string
    {
        $env = loadEnv();
        if (array_key_exists($key, $env)) {
            return $env[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        return $default;
    }
}
