<?php

declare(strict_types=1);

namespace backend\components;

use common\entities\Admin;
use common\repositories\AdminRepositoryInterface;
use Yii;
use yii\web\IdentityInterface;

final readonly class AdminIdentity implements IdentityInterface
{
    private function __construct(private Admin $admin)
    {
    }

    public static function fromEntity(Admin $admin): self
    {
        return new self($admin);
    }

    public static function findIdentity($id): ?self
    {
        $admin = Yii::$container->get(AdminRepositoryInterface::class)->findById((int) $id);

        return $admin === null ? null : new self($admin);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return null;
    }

    public function entity(): Admin
    {
        return $this->admin;
    }

    public function getId(): int
    {
        return (int) $this->admin->id;
    }

    public function getAuthKey(): string
    {
        return (string) $this->admin->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return hash_equals((string) $this->admin->auth_key, (string) $authKey);
    }
}
