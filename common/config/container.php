<?php

declare(strict_types=1);

use common\repositories\AdminRepository;
use common\repositories\AdminRepositoryInterface;
use common\repositories\CategoryRepository;
use common\repositories\CategoryRepositoryInterface;
use common\repositories\MessageRepository;
use common\repositories\MessageRepositoryInterface;
use common\repositories\NoteRepository;
use common\repositories\NoteRepositoryInterface;
use common\repositories\RevokedTokenRepository;
use common\repositories\RevokedTokenRepositoryInterface;
use common\repositories\UserRepository;
use common\repositories\UserRepositoryInterface;
use common\services\AdminService;
use common\services\AuthService;
use common\services\CategoryService;
use common\services\DashboardService;
use common\services\EventLogger;
use common\services\EventLoggerInterface;
use common\services\JwtTokenManager;
use common\services\MessageService;
use common\services\NoteService;
use common\services\PasswordHasher;
use common\services\PasswordHasherInterface;
use common\services\TokenManagerInterface;
use common\services\UserService;
use yii\caching\CacheInterface;

return [
    'singletons' => [
        PasswordHasherInterface::class => PasswordHasher::class,
        EventLoggerInterface::class => EventLogger::class,
        CacheInterface::class => static fn (): CacheInterface => Yii::$app->cache,

        UserRepositoryInterface::class => UserRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
        AdminRepositoryInterface::class => AdminRepository::class,
        MessageRepositoryInterface::class => MessageRepository::class,
        RevokedTokenRepositoryInterface::class => RevokedTokenRepository::class,
        NoteRepositoryInterface::class => static fn (): NoteRepositoryInterface => new NoteRepository(
            Yii::$app->cache,
            Yii::$container->get(EventLoggerInterface::class),
            (int) Yii::$app->params['notesCacheTtl'],
        ),
        TokenManagerInterface::class => static function (): TokenManagerInterface {
            $params = Yii::$app->params;

            return new JwtTokenManager(
                revokedTokens: Yii::$container->get(RevokedTokenRepositoryInterface::class),
                secret: (string) $params['jwtSecret'],
                issuer: (string) $params['jwtIssuer'],
                audience: (string) $params['jwtAudience'],
                ttl: (int) $params['jwtTtl'],
                leeway: (int) $params['jwtLeeway'],
            );
        },

        AuthService::class => AuthService::class,
        CategoryService::class => CategoryService::class,
        NoteService::class => NoteService::class,
        UserService::class => UserService::class,
        AdminService::class => AdminService::class,
        MessageService::class => MessageService::class,
        DashboardService::class => DashboardService::class,
    ],
];
