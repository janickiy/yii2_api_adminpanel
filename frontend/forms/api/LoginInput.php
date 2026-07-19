<?php

declare(strict_types=1);

namespace frontend\forms\api;

use common\dtos\LoginUserDto;

final class LoginInput extends RequestInput
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

    public function toDto(): LoginUserDto
    {
        return new LoginUserDto(
            email: (string) $this->email,
            password: (string) $this->password,
        );
    }
}
