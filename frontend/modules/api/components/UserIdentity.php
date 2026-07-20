<?php

declare(strict_types=1);

namespace frontend\modules\api\components;

use common\entities\User;
use common\repositories\UserRepositoryInterface;
use common\services\exceptions\AuthenticationException;
use common\services\TokenManagerInterface;
use Yii;
use yii\web\IdentityInterface;

final readonly class UserIdentity implements IdentityInterface
{
    private function __construct(private User $user)
    {
    }

    public static function findIdentity($id): ?self
    {
        $user = self::users()->findById((int) $id);

        return $user === null ? null : new self($user);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        try {
            $userId = self::tokens()->validateAndGetUserId((string) $token);
        } catch (AuthenticationException) {
            return null;
        }

        return self::findIdentity($userId);
    }

    public function getId(): int
    {
        return (int) $this->user->id;
    }

    public function getAuthKey(): ?string
    {
        return null;
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    private static function users(): UserRepositoryInterface
    {
        return Yii::$container->get(UserRepositoryInterface::class);
    }

    private static function tokens(): TokenManagerInterface
    {
        return Yii::$container->get(TokenManagerInterface::class);
    }
}
