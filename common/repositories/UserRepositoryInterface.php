<?php

declare(strict_types=1);

namespace common\repositories;

use common\entities\User;
use yii\db\ActiveQuery;

interface UserRepositoryInterface
{
    /** @phpstan-impure */
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function save(User $user): User;

    /** @return ActiveQuery<User> */
    public function query(): ActiveQuery;

    public function count(): int;

    public function delete(User $user): void;
}
