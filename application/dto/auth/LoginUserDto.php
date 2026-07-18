<?php

declare(strict_types=1);

namespace application\dto\auth;

use application\dto\BaseDto;

final class LoginUserDto extends BaseDto
{
    public mixed $email = null;
    public mixed $password = null;

    public function rules(): array
    {
        return [
            ['email', 'trim'],
            ['email', 'filter', 'filter' => static fn (mixed $value): mixed => is_string($value)
                ? strtolower($value)
                : $value],
            [['email', 'password'], 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['password', 'string', 'max' => 255],
        ];
    }

    public function emailValue(): string
    {
        return strtolower(trim((string) $this->email));
    }

    public function passwordValue(): string
    {
        return (string) $this->password;
    }
}
