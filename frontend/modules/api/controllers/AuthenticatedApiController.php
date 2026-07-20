<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;

abstract class AuthenticatedApiController extends BaseApiController
{
    /**
     * @param array<string, mixed> $behaviors
     * @return array<string, mixed>
     */
    protected function withBearerAuthentication(array $behaviors): array
    {
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'user' => Yii::$app->get('apiUser'),
        ];

        return $behaviors;
    }
}
