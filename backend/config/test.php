<?php

declare(strict_types=1);

return [
    'id' => 'yii2-api-adminpanel-backend-tests',
    'bootstrap' => [
        \tests\Support\MailerBootstrap::class,
    ],
    'components' => [
        'assetManager' => [
            'basePath' => dirname(__DIR__) . '/web/assets',
        ],
        'request' => [
            'cookieValidationKey' => 'test-backend',
            'enableCsrfValidation' => false,
        ],
    ],
];
