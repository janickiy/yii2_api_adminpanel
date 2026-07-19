<?php

declare(strict_types=1);

namespace common\models;

use domain\exceptions\AuthenticationException;
use domain\services\TokenManagerInterface;
use infrastructure\persistence\records\UserRecord;
use Yii;
use yii\web\IdentityInterface;

/**
 * Yii web identity kept as a compatibility adapter around the layered domain.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $created_at
 * @property string $updated_at
 */
class User extends UserRecord implements IdentityInterface
{
    public static function findIdentity($id): ?self
    {
        $identity = static::findOne(['id' => (int) $id]);

        return $identity instanceof self ? $identity : null;
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        try {
            $userId = self::tokenManager()->validateAndGetUserId((string) $token);
        } catch (AuthenticationException) {
            return null;
        }

        return self::findIdentity($userId);
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getAuthKey(): ?string
    {
        return null;
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    private static function tokenManager(): TokenManagerInterface
    {
        return Yii::$container->get(TokenManagerInterface::class);
    }
}
