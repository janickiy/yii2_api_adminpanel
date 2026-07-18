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

if (!function_exists('app_secret')) {
    /**
     * @param list<string> $knownInsecureValues
     */
    function app_secret(
        string $name,
        mixed $value,
        string $developmentDefault,
        array $knownInsecureValues = [],
    ): string {
        $secret = trim((string) $value);
        $environment = strtolower((string) env('APP_ENV', defined('YII_ENV') ? YII_ENV : 'dev'));
        $isProduction = $environment === 'prod'
            || (defined('YII_ENV_PROD') && YII_ENV_PROD);

        if (!$isProduction) {
            return $secret !== '' ? $secret : $developmentDefault;
        }

        $insecure = $secret === ''
            || strlen($secret) < 32
            || in_array($secret, $knownInsecureValues, true);
        if ($insecure) {
            throw new RuntimeException(sprintf(
                '%s must be set to a unique secret of at least 32 characters in production.',
                $name,
            ));
        }

        return $secret;
    }
}
