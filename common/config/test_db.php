<?php

declare(strict_types=1);

$db = require __DIR__ . '/db.php';
$db['dsn'] = (string) env(
    'TEST_DB_DSN',
    sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        (string) env('DB_HOST', 'postgres'),
        (int) env('DB_PORT', 5432),
        (string) env('TEST_DB_DATABASE', 'notes_test'),
    ),
);
$db['username'] = (string) env('TEST_DB_USERNAME', env('DB_USERNAME', 'notes'));
$db['password'] = (string) env('TEST_DB_PASSWORD', env('DB_PASSWORD', 'notes'));
$db['enableSchemaCache'] = false;

return $db;
