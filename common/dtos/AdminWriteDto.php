<?php

declare(strict_types=1);

namespace common\dtos;

final class AdminWriteDto
{
    public string $name;
    public string $login;
    public string $role;
    public ?string $password;

    public function __construct(
        string $name,
        string $login,
        string $role,
        ?string $password,
    ) {
        $this->name = $name;
        $this->login = $login;
        $this->role = $role;
        $this->password = $password;
    }
}
