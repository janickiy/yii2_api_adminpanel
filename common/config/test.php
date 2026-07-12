<?php

declare(strict_types=1);

return [
    'components' => [
        'db' => require __DIR__ . '/test_db.php',
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'messageClass' => \yii\symfonymailer\Message::class,
            'useFileTransport' => true,
            'viewPath' => '@common/mail',
        ],
    ],
];
