<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        env('DB_HOST', 'mysql_db'),
        env('DB_PORT', '3306'),
        env('DB_DATABASE', 'yii2_db')
    ),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', 'root_password'),
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
