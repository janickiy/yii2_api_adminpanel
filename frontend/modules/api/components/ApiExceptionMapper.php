<?php

declare(strict_types=1);

namespace frontend\modules\api\components;

use common\repositories\PersistenceException;
use common\services\exceptions\AuthenticationException;
use common\services\exceptions\ConflictException;
use common\services\exceptions\NotFoundException;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;

final class ApiExceptionMapper
{
    public function authentication(AuthenticationException $exception): never
    {
        throw new UnauthorizedHttpException($exception->getMessage(), 0, $exception);
    }

    public function conflict(ConflictException $exception): never
    {
        throw new ConflictHttpException($exception->getMessage(), 0, $exception);
    }

    public function notFound(NotFoundException $exception): never
    {
        throw new NotFoundHttpException($exception->getMessage(), 0, $exception);
    }

    public function validationField(
        string $field,
        NotFoundException $exception,
    ): never {
        throw ValidationHttpException::forField($field, $exception->getMessage());
    }

    public function persistence(
        PersistenceException $exception,
        string $publicMessage,
    ): never {
        throw new ServerErrorHttpException($publicMessage, 0, $exception);
    }
}
