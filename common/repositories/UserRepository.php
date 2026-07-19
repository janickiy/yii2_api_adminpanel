<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\User;
use Throwable;
use yii\db\ActiveQuery;

final class UserRepository extends AbstractActiveRecordRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        try {
            $user = User::find()->where(['email' => $email])->one();

            return $user instanceof User ? $user : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the user by email.', $exception);
        }
    }

    public function findById(int $id): ?User
    {
        try {
            $user = User::findOne(['id' => $id]);

            return $user instanceof User ? $user : null;
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to find the user by id.', $exception);
        }
    }

    public function save(User $user): User
    {
        return $this->saveRecord($user, 'Unable to save the user.');
    }

    public function query(): ActiveQuery
    {
        return User::find()->orderBy(['id' => SORT_DESC]);
    }

    public function count(): int
    {
        try {
            return (int) $this->query()->count();
        } catch (Throwable $exception) {
            throw PersistenceException::wrap('Unable to count users.', $exception);
        }
    }

    public function delete(User $user): void
    {
        $this->deleteRecord($user, 'Unable to delete the user.');
    }
}
