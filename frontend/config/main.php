<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';

$config = [
    'id' => 'yii2-api-adminpanel-frontend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'frontend\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'api' => [
            'class' => \frontend\modules\api\Module::class,
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
            'cookieValidationKey' => env('FRONTEND_COOKIE_VALIDATION_KEY', env('COOKIE_VALIDATION_KEY', 'yii2-api-frontend-cookie-key')),
            'enableCsrfValidation' => true,
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'apiUser' => [
            'class' => \yii\web\User::class,
            'identityClass' => \common\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'user' => [
            'identityClass' => \common\models\User::class,
            'enableAutoLogin' => false,
            'loginUrl' => null,
        ],
        'session' => [
            'name' => 'advanced-frontend',
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
                'GET /' => 'site/index',
                'GET api/documentation' => 'api/documentation/index',
                'GET docs' => 'api/documentation/spec',

                'GET api/v1' => 'api/site/index',
                'POST api/v1/register' => 'api/auth/register',
                'POST api/v1/login' => 'api/auth/login',
                'POST api/v1/logout' => 'api/auth/logout',

                'GET api/v1/notes' => 'api/note/index',
                'GET api/v1/notes/<id:\d+>' => 'api/note/show',
                'POST api/v1/notes/store' => 'api/note/store',
                'PUT api/v1/notes/update/<id:\d+>' => 'api/note/update',
                'DELETE api/v1/notes/delete/<id:\d+>' => 'api/note/delete',
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
}

return $config;
