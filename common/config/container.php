<?php

declare(strict_types=1);

use application\services\AuthService;
use application\services\CategoryService;
use application\services\NoteService;
use domain\mappers\CategoryDataMapperInterface;
use domain\mappers\NoteDataMapperInterface;
use domain\mappers\UserDataMapperInterface;
use domain\repositories\CategoryRepositoryInterface;
use domain\repositories\NoteRepositoryInterface;
use domain\repositories\UserRepositoryInterface;
use domain\services\EventLoggerInterface;
use domain\services\PasswordHasherInterface;
use domain\services\TokenManagerInterface;
use infrastructure\logging\YiiEventLogger;
use infrastructure\persistence\mappers\CategoryDataMapper;
use infrastructure\persistence\mappers\NoteDataMapper;
use infrastructure\persistence\mappers\UserDataMapper;
use infrastructure\persistence\repositories\ActiveRecordCategoryRepository;
use infrastructure\persistence\repositories\ActiveRecordNoteRepository;
use infrastructure\persistence\repositories\ActiveRecordUserRepository;
use infrastructure\security\FirebaseJwtTokenManager;
use infrastructure\security\YiiPasswordHasher;
use yii\caching\CacheInterface;

return [
    'singletons' => [
        UserDataMapperInterface::class => UserDataMapper::class,
        NoteDataMapperInterface::class => NoteDataMapper::class,
        CategoryDataMapperInterface::class => CategoryDataMapper::class,
        PasswordHasherInterface::class => YiiPasswordHasher::class,
        EventLoggerInterface::class => YiiEventLogger::class,
        CacheInterface::class => static fn (): CacheInterface => Yii::$app->cache,
        TokenManagerInterface::class => static function (): TokenManagerInterface {
            $params = Yii::$app->params;

            return new FirebaseJwtTokenManager(
                secret: (string) $params['jwtSecret'],
                issuer: (string) $params['jwtIssuer'],
                audience: (string) $params['jwtAudience'],
                ttl: (int) $params['jwtTtl'],
                leeway: (int) $params['jwtLeeway'],
            );
        },
        UserRepositoryInterface::class => ActiveRecordUserRepository::class,
        CategoryRepositoryInterface::class => ActiveRecordCategoryRepository::class,
        NoteRepositoryInterface::class => static fn (): NoteRepositoryInterface =>
            new ActiveRecordNoteRepository(
                Yii::$container->get(NoteDataMapperInterface::class),
                Yii::$app->cache,
                Yii::$container->get(EventLoggerInterface::class),
                (int) Yii::$app->params['notesCacheTtl'],
            ),
        AuthService::class => AuthService::class,
        CategoryService::class => CategoryService::class,
        NoteService::class => NoteService::class,
    ],
];
