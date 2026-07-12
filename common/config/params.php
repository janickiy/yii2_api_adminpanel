<?php

declare(strict_types=1);

return [
    'adminEmail' => env('ADMIN_EMAIL', 'admin@example.com'),
    'senderEmail' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    'senderName' => env('MAIL_FROM_NAME', env('APP_NAME', 'Yii2 API Adminpanel')),
    'jwtSecret' => env('JWT_SECRET', 'yii2_api_adminpanel_dev_secret_change_me_32_chars_minimum_2026'),
    'jwtIssuer' => env('APP_URL', 'http://localhost:8082'),
    'jwtTtl' => (int) env('JWT_TTL', 3600),
];
