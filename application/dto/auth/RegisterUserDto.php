<?php

declare(strict_types=1);

namespace application\dto\auth;

use application\dto\BaseDto;

final class RegisterUserDto extends BaseDto
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

    public function nameValue(): string
    {
        return trim((string) $this->name);
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
