<?php

declare(strict_types=1);

namespace domain\repositories;

use domain\entities\User;

interface UserRepositoryInterface
{
    /** @phpstan-impure */
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function save(User $user): User;
}
