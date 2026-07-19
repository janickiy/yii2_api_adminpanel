<?php

declare(strict_types=1);

use backend\services\AdminManagementService;
use backend\services\CategoryManagementService;
use backend\services\DashboardMetricsService;
use backend\services\MessageManagementService;
use backend\services\NoteManagementService;
use backend\services\RecordDeleter;
use backend\services\UserManagementService;

$params = require __DIR__ . '/params.php';
$cookieValidationKey = app_secret(
    'BACKEND_COOKIE_VALIDATION_KEY or COOKIE_VALIDATION_KEY',
    env('BACKEND_COOKIE_VALIDATION_KEY', env('COOKIE_VALIDATION_KEY')),
    'yii2-api-backend-cookie-key',
    [
        'yii2-api-backend-cookie-key',
        'local-development-cookie-key-change-me',
        'replace-with-a-long-random-cookie-key',
    ],
);

$config = [
    'id' => 'yii2-api-adminpanel-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'container' => [
        'singletons' => [
            AdminManagementService::class => AdminManagementService::class,
            CategoryManagementService::class => CategoryManagementService::class,
            DashboardMetricsService::class => DashboardMetricsService::class,
            MessageManagementService::class => MessageManagementService::class,
            NoteManagementService::class => NoteManagementService::class,
            RecordDeleter::class => RecordDeleter::class,
            UserManagementService::class => UserManagementService::class,
        ],
    ],
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'cookieValidationKey' => $cookieValidationKey,
            'enableCsrfValidation' => true,
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'user' => [
            'identityClass' => \common\models\Admin::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
            'identityCookie' => [
                'name' => '_backendIdentity',
                'httpOnly' => true,
            ],
        ],
        'session' => [
            'name' => 'advanced-backend',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                ],
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['info', 'warning', 'error'],
                    'categories' => ['application*'],
                    'logFile' => '@backend/runtime/logs/events.log',
                    'logVars' => [],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'normalizer' => [
                'class' => \yii\web\UrlNormalizer::class,
                'action' => null,
            ],
            'rules' => [
                'GET /' => 'site/home',
                'GET login' => 'site/login',
                'POST login' => 'site/login',
                'POST logout' => 'site/logout',
                'GET cp' => 'dashboard/index',

                'GET cp/admins' => 'admin/index',
                'GET cp/admins/create' => 'admin/create',
                'POST cp/admins' => 'admin/store',
                'GET cp/admins/<id:\d+>/edit' => 'admin/edit',
                'PUT,PATCH cp/admins/<id:\d+>' => 'admin/update',
                'DELETE cp/admins/<id:\d+>' => 'admin/destroy',

                'GET cp/users' => 'users/index',
                'GET cp/users/create' => 'users/create',
                'POST cp/users' => 'users/store',
                'GET cp/users/<id:\d+>/edit' => 'users/edit',
                'PUT,PATCH cp/users/<id:\d+>' => 'users/update',
                'DELETE cp/users/<id:\d+>' => 'users/destroy',

                'GET cp/categories' => 'category/index',
                'GET cp/categories/create' => 'category/create',
                'POST cp/categories' => 'category/store',
                'GET cp/categories/<id:\d+>/edit' => 'category/edit',
                'PUT,PATCH cp/categories/<id:\d+>' => 'category/update',
                'DELETE cp/categories/<id:\d+>' => 'category/destroy',

                'GET cp/notes' => 'notes/index',
                'GET cp/notes/<id:\d+>/edit' => 'notes/edit',
                'PUT,PATCH cp/notes/<id:\d+>' => 'notes/update',
                'DELETE cp/notes/<id:\d+>' => 'notes/destroy',

                'GET cp/messages' => 'messages/index',
                'GET cp/messages/<id:\d+>' => 'messages/view',
                'POST cp/messages/<id:\d+>/status' => 'messages/status',
                'DELETE cp/messages/<id:\d+>' => 'messages/destroy',

            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

return $config;
