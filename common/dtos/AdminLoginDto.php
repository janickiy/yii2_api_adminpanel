<?php

declare(strict_types=1);

namespace common\dtos;

final class AdminLoginDto
{
    public string $login;
    public string $password;
    public bool $remember;

    public function __construct(string $login, string $password, bool $remember)
    {
        $this->login = $login;
        $this->password = $password;
        $this->remember = $remember;
    }
}
