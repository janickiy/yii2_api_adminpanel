<?php

declare(strict_types=1);

namespace common\services;

use InvalidArgumentException;
use Throwable;
use Yii;

final class PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        if ($password === '') {
            throw new InvalidArgumentException('Password must not be empty.');
        }

        return Yii::$app->security->generatePasswordHash($password);
    }

    public function verify(string $password, string $passwordHash): bool
    {
        if ($password === '' || $passwordHash === '') {
            return false;
        }

        try {
            return Yii::$app->security->validatePassword($password, $passwordHash);
        } catch (Throwable) {
            return false;
        }
    }
}
