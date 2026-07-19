<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$container = require __DIR__ . '/container.php';
$cache = strtolower((string) env('CACHE_DRIVER', 'file')) === 'memcached'
    ? [
        'class' => \yii\caching\MemCache::class,
        'useMemcached' => true,
        'servers' => [[
            'host' => (string) env('CACHE_HOST', 'memcached'),
            'port' => (int) env('CACHE_PORT', 11211),
            'weight' => 100,
        ]],
    ]
    : [
        'class' => \yii\caching\FileCache::class,
    ];

return [
    'name' => env('APP_NAME', 'Yii2 API Adminpanel'),
    'language' => (string) env('APP_LANGUAGE', 'ru-RU'),
    'timeZone' => (string) env('APP_TIMEZONE', 'Europe/Moscow'),
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'container' => $container,
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'cache' => $cache,
        'db' => $db,
        'mutex' => [
            'class' => \yii\mutex\PgsqlMutex::class,
            'db' => 'db',
        ],
    ],
    'params' => $params,
];
