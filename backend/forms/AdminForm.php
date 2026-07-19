<?php

declare(strict_types=1);

namespace backend\forms;

use common\dtos\AdminWriteDto;
use common\entities\Admin;

final class AdminForm extends BackofficeForm
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public ?int $id = null;
    public ?string $login = null;
    public ?string $name = null;
    public ?string $role = null;
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
            [['login', 'name'], 'required'],
            [['login', 'name'], 'trim'],
            ['login', 'string', 'min' => 3, 'max' => 120],
            ['name', 'string', 'min' => 3, 'max' => 160],
            ['id', 'integer'],
            ['role', 'required'],
            ['role', 'in', 'range' => array_keys(self::roleLabels())],
            ['password', 'required', 'on' => self::SCENARIO_CREATE],
            [['password', 'password_again'], 'string', 'min' => 8],
            ['password_again', 'compare', 'compareAttribute' => 'password'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => 'логин',
            'name' => 'имя',
            'role' => 'роль',
            'password' => 'пароль',
            'password_again' => 'повтор пароля',
        ];
    }

    /** @return array<string, string> */
    public static function roleLabels(): array
    {
        return [
            Admin::ROLE_ADMIN => 'Администратор',
            Admin::ROLE_MODERATOR => 'Модератор',
        ];
    }

    public function loadFromAdmin(Admin $admin): void
    {
        $this->id = (int) $admin->id;
        $this->login = $admin->login;
        $this->name = $admin->name;
        $this->role = $admin->role;
    }

    public function toDto(): AdminWriteDto
    {
        return new AdminWriteDto(
            name: (string) $this->name,
            login: (string) $this->login,
            role: (string) $this->role,
            password: $this->password === null || $this->password === '' ? null : $this->password,
        );
    }
}
