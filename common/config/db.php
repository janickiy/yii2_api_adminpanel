<?php

declare(strict_types=1);

return [
    'class' => \yii\db\Connection::class,
    'dsn' => sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        (string) env('DB_HOST', 'postgres'),
        (int) env('DB_PORT', 5432),
        (string) env('DB_DATABASE', 'notes'),
    ),
    'username' => (string) env('DB_USERNAME', 'notes'),
    'password' => (string) env('DB_PASSWORD', 'notes'),
    'charset' => 'utf8',
    'enableSchemaCache' => (bool) env('DB_SCHEMA_CACHE', false),
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
