<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$root = dirname(__DIR__, 2);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => $value,
        };
    }
}
