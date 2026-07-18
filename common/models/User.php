<?php

declare(strict_types=1);

namespace common\models;

use domain\entities\User as UserEntity;
use domain\exceptions\AuthenticationException;
use domain\mappers\UserDataMapperInterface;
use domain\services\PasswordHasherInterface;
use domain\services\TokenManagerInterface;
use infrastructure\persistence\records\UserRecord;
use RuntimeException;
use Yii;
use yii\db\ActiveQuery;
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

    /**
     * @return ActiveQuery<Notes>
     */
    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(Notes::class, ['user_id' => 'id']);
    }

    public function setPassword(string $password): void
    {
        $this->password = self::passwordHasher()->hash($password);
    }

    public function validatePassword(string $password): bool
    {
        return self::passwordHasher()->verify($password, (string) $this->password);
    }

    public function generateAccessToken(): string
    {
        return self::tokenManager()->issue($this->toDomainEntity());
    }

    public static function revokeAccessToken(string $token): void
    {
        self::tokenManager()->revoke($token);
    }

    private function toDomainEntity(): UserEntity
    {
        $entity = self::userMapper()->fromArray($this->getAttributes());

        if (!$entity instanceof UserEntity) {
            throw new RuntimeException('The configured user mapper returned an unexpected entity type.');
        }

        return $entity;
    }

    private static function tokenManager(): TokenManagerInterface
    {
        $service = Yii::$container->get(TokenManagerInterface::class);

        if (!$service instanceof TokenManagerInterface) {
            throw new RuntimeException('TokenManagerInterface is not configured in the Yii DI container.');
        }

        return $service;
    }

    private static function passwordHasher(): PasswordHasherInterface
    {
        $service = Yii::$container->get(PasswordHasherInterface::class);

        if (!$service instanceof PasswordHasherInterface) {
            throw new RuntimeException('PasswordHasherInterface is not configured in the Yii DI container.');
        }

        return $service;
    }

    private static function userMapper(): UserDataMapperInterface
    {
        $mapper = Yii::$container->get(UserDataMapperInterface::class);

        if (!$mapper instanceof UserDataMapperInterface) {
            throw new RuntimeException('UserDataMapperInterface is not configured in the Yii DI container.');
        }

        return $mapper;
    }
}
