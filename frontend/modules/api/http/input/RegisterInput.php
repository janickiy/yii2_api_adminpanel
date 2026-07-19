<?php

declare(strict_types=1);

namespace frontend\modules\api\http\input;

use application\dto\auth\RegisterUserDto;

final class RegisterInput extends RequestInput
{
    public mixed $name = null;
    public mixed $email = null;
    public mixed $password = null;
    public mixed $password_confirmation = null;

    public function rules(): array
    {
        return [
            [['name', 'email'], 'trim'],
            ['email', 'filter', 'filter' => static fn (mixed $value): mixed => is_string($value)
                ? strtolower($value)
                : $value],
            [['name', 'email', 'password', 'password_confirmation'], 'required'],
            ['name', 'string', 'min' => 2, 'max' => 160],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            [['password', 'password_confirmation'], 'string', 'min' => 8, 'max' => 255],
            ['password_confirmation', 'compare', 'compareAttribute' => 'password'],
        ];
    }

    public function toDto(): RegisterUserDto
    {
        return new RegisterUserDto(
            name: (string) $this->name,
            email: (string) $this->email,
            password: (string) $this->password,
        );
    }
}
