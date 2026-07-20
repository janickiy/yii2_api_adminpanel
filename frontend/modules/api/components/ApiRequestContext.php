<?php

declare(strict_types=1);

namespace frontend\modules\api\components;

use RuntimeException;
use Yii;
use yii\web\User;

final class ApiRequestContext
{
    public function userId(): int
    {
        $user = Yii::$app->get('apiUser');
        if (!$user instanceof User || $user->id === null) {
            throw new RuntimeException('The authenticated API user is unavailable.');
        }

        return (int) $user->id;
    }

    public function bearerToken(): string
    {
        $authorization = Yii::$app->request->headers->get('Authorization', '');

        return preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches) === 1
            ? $matches[1]
            : '';
    }
}
