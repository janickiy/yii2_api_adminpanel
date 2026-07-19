<?php

declare(strict_types=1);

namespace backend\forms;

use common\dtos\UserWriteDto;
use common\entities\User;

final class UserForm extends BackofficeForm
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $password_again = null;

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['name', 'email'], 'required'],
            [['name', 'email'], 'trim'],
            ['email', 'filter', 'filter' => static fn (mixed $value): mixed => is_string($value)
                ? strtolower($value)
                : $value],
            ['id', 'integer'],
            ['name', 'string', 'max' => 160],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['password', 'required', 'on' => self::SCENARIO_CREATE],
            [['password', 'password_again'], 'string', 'min' => 8, 'max' => 255],
            ['password_again', 'compare', 'compareAttribute' => 'password'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Имя',
            'email' => 'Email',
            'password' => 'Пароль',
            'password_again' => 'Повтор пароля',
        ];
    }

    public function loadFromUser(User $user): void
    {
        $this->id = (int) $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function toDto(): UserWriteDto
    {
        return new UserWriteDto(
            name: (string) $this->name,
            email: (string) $this->email,
            password: $this->password === null || $this->password === '' ? null : $this->password,
        );
    }
}
