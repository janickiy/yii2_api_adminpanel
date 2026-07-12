<?php

declare(strict_types=1);

return [
    'id' => 'yii2-api-adminpanel-frontend-tests',
    'components' => [
        'assetManager' => [
            'basePath' => dirname(__DIR__) . '/web/assets',
        ],
        'request' => [
            'cookieValidationKey' => 'test-frontend',
            'enableCsrfValidation' => false,
        ],
    ],
];
