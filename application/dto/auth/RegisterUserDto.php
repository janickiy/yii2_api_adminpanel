<?php

declare(strict_types=1);

namespace application\dto\auth;

final readonly class RegisterUserDto
{
    public string $name;
    public string $email;
    public string $password;

    public function __construct(
        string $name,
        string $email,
        string $password,
    ) {
        $this->name = trim($name);
        $this->email = strtolower(trim($email));
        $this->password = $password;
    }
}
