<?php

declare(strict_types=1);

namespace application\dto\auth;

final readonly class LoginUserDto
{
    public string $email;
    public string $password;

    public function __construct(
        string $email,
        string $password,
    ) {
        $this->email = strtolower(trim($email));
        $this->password = $password;
    }
}
