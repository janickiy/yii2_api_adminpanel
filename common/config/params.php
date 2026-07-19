<?php

declare(strict_types=1);

$jwtSecret = app_secret(
    'JWT_SECRET',
    env('JWT_SECRET'),
    'local-development-secret-change-before-production-2026',
    [
        'local-development-secret-change-before-production-2026',
        'replace-with-at-least-32-random-characters',
    ],
);

return [
    'jwtSecret' => $jwtSecret,
    'jwtIssuer' => (string) env('JWT_ISSUER', env('APP_URL', 'http://localhost:8082')),
    'jwtAudience' => (string) env('JWT_AUDIENCE', 'notes-api'),
    'jwtTtl' => (int) env('JWT_TTL', 3600),
    'jwtLeeway' => (int) env('JWT_LEEWAY', 10),
    'notesCacheTtl' => (int) env('NOTES_CACHE_TTL', 120),
];
