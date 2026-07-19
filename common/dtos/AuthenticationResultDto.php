<?php

declare(strict_types=1);

namespace common\dtos;

use common\entities\User;

final class AuthenticationResultDto
{
    public User $user;
    public string $token;

    public function __construct(
        User $user,
        string $token,
    ) {
        $this->user = $user;
        $this->token = $token;
    }
}
