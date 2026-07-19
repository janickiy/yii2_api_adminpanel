<?php

declare(strict_types=1);

namespace common\services;

use common\entities\User;

interface TokenManagerInterface
{
    public function issue(User $user): string;

    public function validateAndGetUserId(string $token): int;

    public function revoke(string $token): void;
}
