<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$cookieValidationKey = app_secret(
    'FRONTEND_COOKIE_VALIDATION_KEY or COOKIE_VALIDATION_KEY',
    env('FRONTEND_COOKIE_VALIDATION_KEY', env('COOKIE_VALIDATION_KEY')),
    'yii2-api-frontend-cookie-key',
    [
        'yii2-api-frontend-cookie-key',
        'local-development-cookie-key-change-me',
        'replace-with-a-long-random-cookie-key',
    ],
);

$config = [
    'id' => 'yii2-api-adminpanel-frontend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'frontend\controllers',
    'bootstrap' => ['log', 'api'],
    'modules' => [
        'api' => [
            'class' => \frontend\modules\api\Module::class,
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
            'cookieValidationKey' => $cookieValidationKey,
            'enableCsrfValidation' => true,
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'apiUser' => [
            'class' => \yii\web\User::class,
            'identityClass' => \frontend\modules\api\components\UserIdentity::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'user' => [
            'identityClass' => \frontend\modules\api\components\UserIdentity::class,
            'enableAutoLogin' => false,
            'loginUrl' => null,
        ],
        'session' => [
            'name' => 'advanced-frontend',
        ],
        'errorHandler' => [
            'class' => \frontend\modules\api\components\ApiAwareErrorHandler::class,
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
                    'logFile' => '@frontend/runtime/logs/events.log',
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
                'GET,POST /' => 'site/index',
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
