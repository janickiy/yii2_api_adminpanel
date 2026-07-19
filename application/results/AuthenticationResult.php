<?php

declare(strict_types=1);

namespace application\results;

use domain\entities\User;

final readonly class AuthenticationResult
{
    public function __construct(
        public User $user,
        public string $token,
    ) {
    }
}
