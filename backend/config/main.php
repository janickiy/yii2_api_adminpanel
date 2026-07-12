<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';

$config = [
    'id' => 'yii2-api-adminpanel-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'cookieValidationKey' => env('BACKEND_COOKIE_VALIDATION_KEY', env('COOKIE_VALIDATION_KEY', 'yii2-api-backend-cookie-key')),
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
                'GET logout' => 'site/logout',
                'POST logout' => 'site/logout',
                'GET cp' => 'dashboard/index',

                'GET cp/admin' => 'admin/index',
                'GET cp/admin/create' => 'admin/create',
                'POST cp/admin/store' => 'admin/store',
                'GET cp/admin/edit/<id:\d+>' => 'admin/edit',
                'PUT cp/admin/update' => 'admin/update',
                'POST cp/admin/update' => 'admin/update',
                'DELETE cp/admin/destroy/<id:\d+>' => 'admin/destroy',

                'GET cp/catalog' => 'catalog/index',
                'GET cp/catalog/create' => 'catalog/create',
                'POST cp/catalog/store' => 'catalog/store',
                'GET cp/catalog/edit/<id:\d+>' => 'catalog/edit',
                'PUT cp/catalog/update' => 'catalog/update',
                'POST cp/catalog/update' => 'catalog/update',
                'DELETE cp/catalog/destroy/<id:\d+>' => 'catalog/destroy',

                'GET cp/notes' => 'notes/index',
                'GET cp/notes/edit/<id:\d+>' => 'notes/edit',
                'PUT cp/notes/update' => 'notes/update',
                'POST cp/notes/update' => 'notes/update',
                'DELETE cp/notes/destroy/<id:\d+>' => 'notes/destroy',

                'GET cp/datatable/notes' => 'datatable/notes',
                'GET cp/datatable/admin' => 'datatable/admin',
                'GET cp/datatable/catalogs' => 'datatable/catalogs',
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
